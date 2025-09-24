<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\MaintenanceTaskRepository;
use App\Service\PerformanceMetricsService;
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
        private PerformanceMetricsService $metrics
    ) {}

    #[Route('', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        $from = new \DateTime($request->query->get('from', 'first day of this month 00:00'));
        $to = new \DateTime($request->query->get('to', 'last day of this month 23:59:59'));

        // Get performance data
        $summary = $this->taskRepository->getPerformanceSummary($from, $to);
        $users = $this->taskRepository->getAssignedUsersPerformance($from, $to);
        $ranking = $this->metrics->rankUsers($users);

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

        // Get user details and performance metrics
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }

        // Get user's performance data
        $user = $this->taskRepository->getUserPerformance($user, $from, $to);
        $ranking = $this->metrics->rankUsers([$user]);
        $userScore = !empty($ranking) ? $ranking[0] : null;

        // Get user's tasks for the period using QueryBuilder for proper date range filtering
        $qb = $this->taskRepository->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.status = :status')
            ->andWhere('t.closedAt BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('status', 'CLOSED')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('t.closedAt', 'DESC');

        $tasks = $qb->getQuery()->getResult();

        // Get task status distribution for the chart if the method exists
        $statusDistribution = [];
        if (method_exists($this->taskRepository, 'getUserTaskStatusDistribution')) {
            $statusDistribution = $this->taskRepository->getUserTaskStatusDistribution($user, $from, $to);
        }

        return $this->render('admin/performance/user.html.twig', [
            'user' => $user,
            'from' => $from,
            'to' => $to,
            'performance' => $userScore,
            'tasks' => $tasks,
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


