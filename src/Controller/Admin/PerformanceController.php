<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\MaintenanceTask;
use App\Entity\Ticket;
use App\Repository\MaintenanceTaskRepository;
use App\Repository\UserRepository;
use App\Service\PerformanceMetricsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/performance', name: 'admin_performance_')]
class PerformanceController extends AbstractController
{
    public function __construct(
        private MaintenanceTaskRepository $taskRepository,
        private PerformanceMetricsService $metrics,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        $from = new \DateTime($request->query->get('from', 'first day of this month 00:00'));
        $to = new \DateTime($request->query->get('to', 'last day of this month 23:59:59'));

        // Get performance data (DB-agnostic, computed in PHP)
        $summary = $this->taskRepository->getPerformanceSummaryPhp($from, $to);
        $rows = $this->taskRepository->getAssignedUsersPerformancePhp($from, $to);

        // Ensure ALL users appear, even with zero tasks
        $rowsById = [];
        foreach ($rows as $r) {
            $rowsById[(int) ($r['userId'] ?? 0)] = $r;
        }

        // Count ticket assignments per user in the same period
        $assignmentsQb = $this->entityManager->getRepository(\App\Entity\TicketAssignment::class)
            ->createQueryBuilder('ta')
            ->leftJoin('ta.user', 'u')
            ->select('u.id AS userId, COUNT(ta.id) AS assignedCount')
            ->andWhere('ta.assignedAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('u.id');

        $assignmentRows = $assignmentsQb->getQuery()->getArrayResult();
        $assignedByUser = [];
        foreach ($assignmentRows as $ar) {
            $assignedByUser[(int) $ar['userId']] = (int) $ar['assignedCount'];
        }

        // Open tickets assigned per user (within period)
        $openQb = $this->entityManager->getRepository(\App\Entity\Ticket::class)
            ->createQueryBuilder('to')
            ->leftJoin('to.assignedTo', 'ou')
            ->select('ou.id AS userId, COUNT(to.id) AS openCount')
            ->andWhere('to.status != :tkCompleted')
            ->andWhere('to.updatedAt BETWEEN :from AND :to')
            ->setParameter('tkCompleted', \App\Entity\Ticket::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('ou.id');
        $openRows = $openQb->getQuery()->getArrayResult();
        $openByUser = [];
        foreach ($openRows as $or) {
            $openByUser[(int) $or['userId']] = (int) $or['openCount'];
        }

        // Ticket completion metrics per user (attribute to responsible assignee at completion time)
        $tickets = $this->entityManager->getRepository(\App\Entity\Ticket::class)
            ->createQueryBuilder('tk2')
            ->andWhere('tk2.status = :tkCompleted')
            ->andWhere('tk2.updatedAt BETWEEN :from AND :to')
            ->setParameter('tkCompleted', \App\Entity\Ticket::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getResult();
        $aggTc = [];
        $userTicketsDetail = [];
        foreach ($tickets as $tk) {
            if (!$tk instanceof \App\Entity\Ticket) { continue; }
            $createdAt = $tk->getCreatedAt();
            $updatedAt = $tk->getUpdatedAt();
            if (!$createdAt || !$updatedAt) { continue; }

            // Determine responsible user: latest assignment at or before completion time
            $responsibleUser = null;
            $latestAt = null;
            foreach ($tk->getOrderedAssignments() as $assignment) {
                $assignedAt = $assignment->getAssignedAt();
                if ($assignedAt && $assignedAt <= $updatedAt) {
                    if ($latestAt === null || $assignedAt > $latestAt) {
                        $latestAt = $assignedAt;
                        $responsibleUser = $assignment->getUser();
                    }
                }
            }
            // Fallback to current assignedTo if no assignment history found
            if (!$responsibleUser && $tk->getAssignedTo()) {
                $responsibleUser = $tk->getAssignedTo();
            }
            if (!$responsibleUser) { continue; }

            $uid = (int) $responsibleUser->getId();
            if (!isset($aggTc[$uid])) {
                $aggTc[$uid] = ['count'=>0,'sumMin'=>0.0,'last'=>null,'sumFirstActionMin'=>0.0,'firstActionCount'=>0];
            }
            $aggTc[$uid]['count']++;
            $aggTc[$uid]['sumMin'] += max(0, ($updatedAt->getTimestamp() - $createdAt->getTimestamp())/60);

            // time to first action: from createdAt to first assignment time for this responsible user
            $assignedAtForUser = $tk->getUserAssignmentTime($responsibleUser);
            if ($assignedAtForUser) {
                $aggTc[$uid]['sumFirstActionMin'] += max(0, ($assignedAtForUser->getTimestamp() - $createdAt->getTimestamp())/60);
                $aggTc[$uid]['firstActionCount']++;
            }
            if (!$aggTc[$uid]['last'] || $updatedAt > $aggTc[$uid]['last']) {
                $aggTc[$uid]['last'] = $updatedAt;
            }
        }
        $ticketMetrics = [];
        foreach ($aggTc as $uid => $v) {
            $ticketMetrics[$uid] = [
                'ticketsCompleted' => $v['count'],
                'ticketAvgResolutionMin' => $v['count'] > 0 ? $v['sumMin']/$v['count'] : 0.0,
                'lastTicketUpdate' => $v['last'],
                'avgFirstActionMin' => $v['firstActionCount'] > 0 ? $v['sumFirstActionMin']/$v['firstActionCount'] : null,
            ];
        }
        $allUsers = $this->userRepository->findAll();
        foreach ($allUsers as $u) {
            if (!$u instanceof \App\Entity\User) { continue; }
            $uid = (int) $u->getId();
            if (!isset($rowsById[$uid])) {
                $rowsById[$uid] = [
                    'userId' => $uid,
                    'username' => method_exists($u, 'getUsername') ? $u->getUsername() : ($u->getEmail() ?? ''),
                    'nombre' => method_exists($u, 'getNombre') ? $u->getNombre() : null,
                    'apellido' => method_exists($u, 'getApellido') ? $u->getApellido() : null,
                    'cerrados' => 0,
                    'tmrHoras' => 0.0,
                    'slaPct' => 0.0,
                    'reabPct' => 0.0,
                    'ticketsAssigned' => $assignedByUser[$uid] ?? 0,
                    'ticketsCompleted' => $ticketMetrics[$uid]['ticketsCompleted'] ?? 0,
                    'ticketAvgResolutionMin' => $ticketMetrics[$uid]['ticketAvgResolutionMin'] ?? 0.0,
                    'lastTicketUpdate' => $ticketMetrics[$uid]['lastTicketUpdate'] ?? null,
                    'avgFirstActionMin' => $ticketMetrics[$uid]['avgFirstActionMin'] ?? null,
                    'openAssigned' => $openByUser[$uid] ?? 0,
                ];
            } else {
                // If exists, just add ticketsAssigned metric
                $rowsById[$uid]['ticketsAssigned'] = $assignedByUser[$uid] ?? 0;
                $rowsById[$uid]['ticketsCompleted'] = $ticketMetrics[$uid]['ticketsCompleted'] ?? 0;
                $rowsById[$uid]['ticketAvgResolutionMin'] = $ticketMetrics[$uid]['ticketAvgResolutionMin'] ?? 0.0;
                $rowsById[$uid]['lastTicketUpdate'] = $ticketMetrics[$uid]['lastTicketUpdate'] ?? null;
                $rowsById[$uid]['avgFirstActionMin'] = $ticketMetrics[$uid]['avgFirstActionMin'] ?? null;
                $rowsById[$uid]['openAssigned'] = $openByUser[$uid] ?? 0;
            }
        }
        $rowsAll = array_values($rowsById);
        $ranking = $this->metrics->rankUsers($rowsAll);

        return $this->render('admin/performance/dashboard.html.twig', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'ranking' => $ranking
        ]);
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $from = new \DateTime($request->query->get('from', 'first day of this month 00:00'));
        $to = new \DateTime($request->query->get('to', 'last day of this month 23:59:59'));

        // Get the data to export
        $users = $this->taskRepository->getAssignedUsersPerformance($from, $to);
        $ranking = $this->metrics->rankUsers($users);

        // Generate CSV content
        $lines = ["Usuario;Cerrados;TMR(h);SLA(%);Reabiertos(%);Score"];
        foreach ($ranking as $r) {
            $s = $r['score'];
            $name = trim(($r['nombre'] ?? '').' '.($r['apellido'] ?? '')) ?: ($r['username'] ?? 'N/D');
            $lines[] = sprintf(
                "%s;%d;%.1f;%.0f;%.1f;%.1f",
                $name, $s['raw']['cerr'], $s['raw']['tmr'], $s['raw']['sla'], $s['raw']['reab'], $s['final']
            );
        }
        $csv = implode("\n", $lines);

        return new Response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="performance_'.date('Y-m-d').'.csv"',
            ]
        );
    }

    /**
     * Calculate average completion time in hours and minutes
     */
    private function calculateAverageCompletionTime(array $tasks): ?string
    {
        $completedTasks = array_filter($tasks, fn($task) => $task['status'] === 'completed' && isset($task['completedAt']) && $task['completedAt'] !== null);

        if (empty($completedTasks)) {
            return null;
        }

        $totalSeconds = 0;
        $count = 0;

        foreach ($completedTasks as $task) {
            $start = $task['startedAt'] ?? $task['createdAt'];
            $end = $task['completedAt'];

            if ($start && $end) {
                $totalSeconds += $end->getTimestamp() - $start->getTimestamp();
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        $averageSeconds = $totalSeconds / $count;
        $hours = floor($averageSeconds / 3600);
        $minutes = floor(($averageSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    #[Route('/user/{id}', name: 'performance_user', methods: ['GET'])]
    public function user(int $id, Request $request): Response
    {
        $from = new \DateTime($request->query->get('from', 'first day of this month 00:00'));
        $to = new \DateTime($request->query->get('to', 'last day of this month 23:59:59'));

        // Load user entity
        $userEntity = $this->userRepository->find($id);
        if (!$userEntity) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        // User performance aggregate (expects userId)
        $userAgg = $this->taskRepository->getUserPerformance($id, $from, $to);
        $ranking = $this->metrics->rankUsers([$userAgg]);
        $userScore = !empty($ranking) ? $ranking[0] : null;

        // Get user's completed tasks within period
        $qb = $this->taskRepository->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.status = :status')
            ->andWhere('t.completedAt BETWEEN :from AND :to')
            ->setParameter('user', $userEntity)
            ->setParameter('status', MaintenanceTask::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('t.completedAt', 'DESC');

        $tasks = $qb->getQuery()->getResult();

        // Normalize tasks for the template (detail expects plain fields and durationMin)
        $detail = [];
        foreach ($tasks as $t) {
            if (!$t instanceof MaintenanceTask) { continue; }
            $createdAt = $t->getCreatedAt();
            $completedAt = $t->getCompletedAt();
            $durationMin = null;
            if ($createdAt instanceof \DateTimeInterface && $completedAt instanceof \DateTimeInterface) {
                $durationMin = (int) floor(($completedAt->getTimestamp() - $createdAt->getTimestamp()) / 60);
            }
            $detail[] = [
                'id' => $t->getId(),
                'description' => (string) $t->getDescription(),
                'status' => (string) $t->getStatus(),
                'createdAt' => $createdAt,
                // startedAt not present in entity; leave null for template handling
                'startedAt' => null,
                'completedAt' => $completedAt,
                'durationMin' => $durationMin,
                'category' => $t->getCategory() ? $t->getCategory()->getName() : null,
                'fromTicket' => method_exists($t, 'getOriginTicket') && $t->getOriginTicket() !== null,
            ];
        }

        // Build task status distribution from $detail (portable)
        $counts = ['completed' => 0, 'in_progress' => 0, 'pending' => 0];
        foreach ($detail as $row) {
            $st = $row['status'] ?? null;
            if (isset($counts[$st])) { $counts[$st]++; }
        }
        $statusDistribution = [
            'labels' => ['Completadas', 'En Progreso', 'Pendientes'],
            'data' => [
                (int) $counts['completed'],
                (int) $counts['in_progress'],
                (int) $counts['pending'],
            ],
            'colors' => ['#198754', '#0d6efd', '#6c757d']
        ];

        // Ticket KPIs for this user (as responsible executor)
        $tickets = $this->entityManager->getRepository(\App\Entity\Ticket::class)
            ->createQueryBuilder('tk')
            ->andWhere('tk.status = :tkCompleted')
            ->andWhere('tk.updatedAt BETWEEN :from AND :to')
            ->setParameter('tkCompleted', \App\Entity\Ticket::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getResult();

        $ticketsCompleted = 0;
        $sumResolutionMin = 0.0;
        $lastTicketUpdate = null;
        $sumFirstActionMin = 0.0;
        $firstActionCount = 0;
        $userTicketsDetail = [];

        foreach ($tickets as $tk) {
            if (!$tk instanceof \App\Entity\Ticket) { continue; }
            $createdAt = $tk->getCreatedAt();
            $updatedAt = $tk->getUpdatedAt();
            if (!$createdAt || !$updatedAt) { continue; }

            // Identify responsible user at completion time
            $responsibleUser = null;
            $latestAt = null;
            foreach ($tk->getOrderedAssignments() as $assignment) {
                $assignedAt = $assignment->getAssignedAt();
                if ($assignedAt && $assignedAt <= $updatedAt) {
                    if ($latestAt === null || $assignedAt > $latestAt) {
                        $latestAt = $assignedAt;
                        $responsibleUser = $assignment->getUser();
                    }
                }
            }
            if (!$responsibleUser && $tk->getAssignedTo()) {
                $responsibleUser = $tk->getAssignedTo();
            }
            if (!$responsibleUser || $responsibleUser->getId() !== $userEntity->getId()) { continue; }

            // Count as completed for this user
            $ticketsCompleted++;
            $sumResolutionMin += max(0, ($updatedAt->getTimestamp() - $createdAt->getTimestamp())/60);
            if (!$lastTicketUpdate || $updatedAt > $lastTicketUpdate) { $lastTicketUpdate = $updatedAt; }

            $assignedAtForUser = $tk->getUserAssignmentTime($userEntity);
            if ($assignedAtForUser) {
                $sumFirstActionMin += max(0, ($assignedAtForUser->getTimestamp() - $createdAt->getTimestamp())/60);
                $firstActionCount++;
            }

            // Build detail row
            $assignedNames = [];
            foreach ($tk->getOrderedAssignments() as $as) {
                $u = $as->getUser();
                if ($u) {
                    $assignedNames[] = trim(($u->getNombre() ?? '') . ' ' . ($u->getApellido() ?? '')) ?: ($u->getEmail() ?? 'Usuario');
                }
            }
            $userTicketsDetail[] = [
                'id' => $tk->getId(),
                'externo' => $tk->getIdSistemaInterno(),
                'areaOrigen' => $tk->getAreaOrigen(),
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
                'estado' => $tk->getStatus(),
                'asignados' => $assignedNames,
                'resolucionMin' => max(0, ($updatedAt->getTimestamp() - $createdAt->getTimestamp())/60),
                'completadoEl' => $updatedAt,
            ];
        }

        // Tickets assigned and open for this user in the period
        $assignedCount = (int) $this->entityManager->getRepository(\App\Entity\TicketAssignment::class)
            ->createQueryBuilder('ta')
            ->select('COUNT(ta.id)')
            ->andWhere('ta.user = :u')
            ->andWhere('ta.assignedAt BETWEEN :from AND :to')
            ->setParameter('u', $userEntity)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getSingleScalarResult();

        // Count CURRENT open tickets assigned to the user (independent of date range)
        $openAssigned = (int) $this->entityManager->getRepository(\App\Entity\Ticket::class)
            ->createQueryBuilder('to')
            ->select('COUNT(to.id)')
            ->andWhere('to.assignedTo = :u')
            ->andWhere('to.status != :tkCompleted')
            ->setParameter('u', $userEntity)
            ->setParameter('tkCompleted', \App\Entity\Ticket::STATUS_COMPLETED)
            ->getQuery()->getSingleScalarResult();

        $userTickets = [
            'ticketsAssigned' => $assignedCount,
            'ticketsCompleted' => $ticketsCompleted,
            'ticketAvgResolutionMin' => $ticketsCompleted > 0 ? $sumResolutionMin / $ticketsCompleted : 0.0,
            'avgFirstActionMin' => $firstActionCount > 0 ? $sumFirstActionMin / $firstActionCount : null,
            'lastTicketUpdate' => $lastTicketUpdate,
            'openAssigned' => $openAssigned,
        ];

        return $this->render('admin/performance/user.html.twig', [
            'user' => $userEntity,
            'from' => $from,
            'to' => $to,
            'performance' => $userScore,
            'tasks' => $tasks,
            'detail' => $detail,
            'userTickets' => $userTickets,
            'userTicketsDetail' => $userTicketsDetail,
            'status_distribution' => $statusDistribution
        ]);
    }

    #[Route('/data', name: 'data')]
    #[IsGranted('ROLE_AUDITOR')]
    public function data(Request $request): Response
    {
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $status = (array) $request->query->all('status');
        $userIds = (array) $request->query->all('users');

        $now = new \DateTimeImmutable('now');
        $fromDate = $from ? new \DateTimeImmutable($from . ' 00:00:00') : $now->modify('first day of this month')->setTime(0, 0);
        $toDate = $to ? new \DateTimeImmutable($to . ' 23:59:59') : $now->modify('last day of this month')->setTime(23, 59, 59);
        if ($fromDate > $toDate) {
            $tmp = $fromDate; $fromDate = $toDate->modify('first day of this month')->setTime(0,0); $toDate = $tmp->modify('last day of this month')->setTime(23,59,59);
        }

        $qb = $this->entityManager->getRepository(MaintenanceTask::class)->createQueryBuilder('t')
            ->leftJoin('t.assignedTo', 'u')
            ->addSelect('u')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $fromDate)
            ->setParameter('to', $toDate);

        if (!empty($status)) {
            $qb->andWhere('t.status IN (:statuses)')->setParameter('statuses', $status);
        }

        if (!empty($userIds)) {
            $qb->andWhere('u.id IN (:userIds)')->setParameter('userIds', $userIds);
        }

        $tasks = $qb->getQuery()->getResult();

        // Also load completed tickets for auditor performance (closers)
        $tq = $this->entityManager->getRepository(Ticket::class)->createQueryBuilder('tk')
            ->leftJoin('tk.completedBy', 'cu')
            ->addSelect('cu')
            ->andWhere('tk.createdAt BETWEEN :from AND :to')
            ->andWhere('tk.status = :tkCompleted')
            ->setParameter('from', $fromDate)
            ->setParameter('to', $toDate)
            ->setParameter('tkCompleted', Ticket::STATUS_COMPLETED);

        if (!empty($userIds)) {
            $tq->andWhere('cu.id IN (:userIds)')->setParameter('userIds', $userIds);
        }

        $tickets = $tq->getQuery()->getResult();

        $perUser = [];
        $allAssignedCounts = [];

        foreach ($tasks as $task) {
            if (!$task instanceof MaintenanceTask) { continue; }
            $user = $task->getAssignedTo();
            $userId = $user?->getId() ?? 0;
            $userName = $user?->getNombre() ? ($user->getNombre() . ' ' . ($user->getApellido() ?? '')) : ($user?->getEmail() ?? 'Sin asignar');

            if (!isset($perUser[$userId])) {
                $perUser[$userId] = [
                    'userId' => $userId,
                    'userName' => $userName,
                    'assigned' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'pending' => 0,
                    'totalDurationMin' => 0,
                    'avgDurationMin' => 0,
                    'lastActivity' => null,
                    'slaMet' => 0,
                    'slaTotal' => 0,
                ];
            }

            $u =& $perUser[$userId];
            $u['assigned']++;
            $u['lastActivity'] = max($u['lastActivity'] ?? new \DateTimeImmutable('@0'), $task->getUpdatedAt() ?? $task->getCreatedAt());

            $statusVal = $task->getStatus();
            if ($statusVal === MaintenanceTask::STATUS_COMPLETED) {
                $u['completed']++;
                $start = $task->getScheduledDate() ?? $task->getCreatedAt();
                // Prefer startedAt if exists in your domain; using createdAt fallback as per spec
                if (method_exists($task, 'getStartedAt') && $task->getStartedAt() instanceof \DateTimeInterface) {
                    $start = $task->getStartedAt();
                }
                $end = $task->getCompletedAt() ?? $task->getUpdatedAt() ?? $task->getCreatedAt();
                $durationMin = max(0, (int) floor(($end->getTimestamp() - $start->getTimestamp()) / 60));
                $u['avgDurationMin'] = ($u['avgDurationMin'] * ($u['completed'] - 1) + $durationMin) / $u['completed'];
                $u['totalDurationMin'] += $durationMin;
                // SLA simple: 48h
                $u['slaTotal']++;
                if ($durationMin <= 48 * 60) { $u['slaMet']++; }
            } elseif ($statusVal === MaintenanceTask::STATUS_IN_PROGRESS) {
                $u['in_progress']++;
            } else {
                $u['pending']++;
            }
        }

        // Aggregate tickets closed by auditor (credit to completedBy)
        foreach ($tickets as $tk) {
            if (!$tk instanceof Ticket) { continue; }
            $closer = $tk->getCompletedBy();
            if (!$closer) { continue; }
            $uid = $closer->getId();
            $uname = $closer->getNombre() ? ($closer->getNombre() . ' ' . ($closer->getApellido() ?? '')) : ($closer->getEmail() ?? 'Usuario');
            if (!isset($perUser[$uid])) {
                $perUser[$uid] = [
                    'userId' => $uid,
                    'userName' => $uname,
                    'assigned' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'pending' => 0,
                    'totalDurationMin' => 0,
                    'avgDurationMin' => 0,
                    'lastActivity' => null,
                    'slaMet' => 0,
                    'slaTotal' => 0,
                    'ticketsClosed' => 0,
                ];
            }
            $perUser[$uid]['ticketsClosed'] = ($perUser[$uid]['ticketsClosed'] ?? 0) + 1;
            $perUser[$uid]['lastActivity'] = max($perUser[$uid]['lastActivity'] ?? new \DateTimeImmutable('@0'), $tk->getUpdatedAt() ?? $tk->getCreatedAt());
        }

        // Compute averages and effectiveness + ranking
        $table = [];
        $kpis = [
            'totalCompleted' => 0,
            'avgDurationMin' => 0,
            'effectiveness' => 0,
            'inProgress' => 0,
            'pending' => 0,
            'employeeOfMonth' => null,
        ];

        $sumAvg = 0; $avgCount = 0; $bestScore = -1; $bestUser = null;
        foreach ($perUser as $row) {
            $completed = $row['completed'];
            $assigned = max(1, $row['assigned']);
            $avg = $completed > 0 ? round($row['totalDurationMin'] / $completed, 1) : 0;
            $effectiveness = round(($completed / $assigned) * 100, 1);
            $slaPct = $row['slaTotal'] > 0 ? round(($row['slaMet'] / $row['slaTotal']) * 100, 1) : 0;

            // Score: 50% completed tasks, 30% inverse avg duration, 10% SLA, 10% tickets closed
            $completedScore = $completed;
            $ticketsScore = (int) ($row['ticketsClosed'] ?? 0);
            $invAvg = $avg > 0 ? (1 / $avg) : 0;
            $score = 0.5 * $completedScore + 0.3 * $invAvg + 0.1 * ($slaPct / 100) + 0.1 * $ticketsScore;

            if ($score > $bestScore) { $bestScore = $score; $bestUser = $row['userName']; }

            $table[] = [
                'userId' => $row['userId'],
                'userName' => $row['userName'],
                'assigned' => $row['assigned'],
                'completed' => $completed,
                'avgDurationMin' => $avg,
                'effectiveness' => $effectiveness,
                'in_progress' => $row['in_progress'],
                'pending' => $row['pending'],
                'lastActivity' => $row['lastActivity']?->format('Y-m-d H:i'),
                'slaPct' => $slaPct,
                'ticketsClosed' => $ticketsScore,
                'score' => round($score, 4),
            ];

            $kpis['totalCompleted'] += $completed;
            $kpis['inProgress'] += $row['in_progress'];
            $kpis['pending'] += $row['pending'];
            $sumAvg += $avg; $avgCount++;
        }

        $kpis['avgDurationMin'] = $avgCount > 0 ? round($sumAvg / $avgCount, 1) : 0;
        $kpis['effectiveness'] = !empty($perUser) ? round(($kpis['totalCompleted'] / array_sum(array_column($perUser, 'assigned'))) * 100, 1) : 0;
        $kpis['employeeOfMonth'] = $bestUser;

        // Prepare charts
        usort($table, fn($a, $b) => $b['completed'] <=> $a['completed']);
        $bar = [
            'labels' => array_column($table, 'userName'),
            'data' => array_column($table, 'completed'),
        ];

        return $this->json([
            'kpis' => $kpis,
            'bar' => $bar,
            'table' => $table,
            'filters' => [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
                'status' => $status,
                'users' => $userIds,
            ]
        ]);
    }
}


