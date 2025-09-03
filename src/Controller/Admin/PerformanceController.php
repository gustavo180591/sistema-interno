<?php

namespace App\Controller\Admin;

use App\Entity\MaintenanceTask;
use App\Entity\Ticket;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('', name: 'performance_')]
#[IsGranted('ROLE_AUDITOR')]
class PerformanceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {}

    #[Route('/performance', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        $now = new \DateTimeImmutable('now');
        $fromDefault = $now->modify('first day of this month')->setTime(0, 0);
        $toDefault = $now->modify('last day of this month')->setTime(23, 59, 59);

        return $this->render('admin/performance/dashboard.html.twig', [
            'default_from' => $fromDefault->format('Y-m-d'),
            'default_to' => $toDefault->format('Y-m-d'),
            'users' => $this->userRepository->findAll(),
        ]);
    }

    #[Route('/performance/data', name: 'data')]
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


