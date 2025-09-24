<?php

namespace App\Repository;

use App\Entity\MaintenanceTask;
use App\Entity\MaintenanceCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<MaintenanceTask>
 *
 * @method MaintenanceTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaintenanceTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaintenanceTask[]    findAll()
 * @method MaintenanceTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaintenanceTaskRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, MaintenanceTask::class);
        $this->entityManager = $entityManager;
    }
    
    /**
     * Get performance metrics for assigned users
     */
    public function getAssignedUsersPerformance(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // First, get basic user data and counts
        $query = $this->createQueryBuilder('t')
            ->select([
                'u.id as userId',
                'u.nombre',
                'u.apellido',
                'COUNT(DISTINCT t.id) as totalAssigned',
                'SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completedCount',
                'SUM(CASE WHEN t.status = :inProgress THEN 1 ELSE 0 END) as inProgressCount'
            ])
            ->leftJoin('t.assignedTo', 'u')
            ->where('t.createdAt BETWEEN :from AND :to')
            ->andWhere('t.assignedTo IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('totalAssigned', 'DESC')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('completed', 'completed')
            ->setParameter('inProgress', 'in_progress');
            
        $results = $query->getQuery()->getResult();
        
        // If no results, return empty array
        if (empty($results)) {
            return [];
        }
        
        // Get user IDs for the second query
        $userIds = array_map(function($item) {
            return $item['userId'];
        }, $results);
        
        // Get completed tasks with their details for calculation
        $tasksQuery = $this->createQueryBuilder('t')
            ->select([
                'u.id as userId',
                't.createdAt',
                't.completedAt',
                't.priority',
                't.actualDuration',
                't.updatedAt',
                't.status',
                't.id as taskId',
                'u.nombre',
                'u.apellido'
            ])
            ->leftJoin('t.assignedTo', 'u')
            ->where('u.id IN (:userIds)')
            ->andWhere('t.status IN (:completedStatuses)')
            ->andWhere('t.completedAt IS NOT NULL')
            ->setParameter('userIds', $userIds)
            ->setParameter('completedStatuses', [
                MaintenanceTask::STATUS_COMPLETED, 
                MaintenanceTask::STATUS_OVERDUE
            ]);
            
        $tasks = $tasksQuery->getQuery()->getResult();
        
        // Initialize arrays to store totals and counts
        $completionTimes = [];
        $prioritySums = [];
        $priorityCounts = [];
        
        // Process each task to calculate metrics
        foreach ($tasks as $task) {
            $userId = $task['userId'];
            
            // Calculate completion time in minutes
            if ($task['completedAt'] && $task['createdAt']) {
                $minutes = null;
                
                // Calculate from when the task was created to when it was completed
                $createdAt = $task['createdAt'];
                $completedAt = $task['completedAt'];
                
                if ($createdAt && $completedAt && $createdAt <= $completedAt) {
                    $interval = $createdAt->diff($completedAt);
                    $minutes = $interval->days * 24 * 60;  // Convert days to minutes
                    $minutes += $interval->h * 60;          // Convert hours to minutes
                    $minutes += $interval->i;                // Add minutes
                    $minutes += $interval->s / 60;           // Convert seconds to minutes
                }
                
                if ($minutes !== null) {
                    if (!isset($completionTimes[$userId])) {
                        $completionTimes[$userId] = [
                            'sum' => 0,
                            'count' => 0
                        ];
                    }
                    $completionTimes[$userId]['sum'] += $minutes;
                    $completionTimes[$userId]['count']++;
                }
            }
            
            // Calculate priority average
            if ($task['priority']) {
                $priorityValue = 0;
                switch ($task['priority']) {
                    case 'high': $priorityValue = 3; break;
                    case 'medium': $priorityValue = 2; break;
                    case 'low': $priorityValue = 1; break;
                }
                
                if (!isset($prioritySums[$userId])) {
                    $prioritySums[$userId] = 0;
                    $priorityCounts[$userId] = 0;
                }
                $prioritySums[$userId] += $priorityValue;
                $priorityCounts[$userId]++;
            }
        }
        
        // Calculate averages
        $avgMap = [];
        foreach (array_unique(array_column($results, 'userId')) as $userId) {
            $avgMap[$userId] = [
                'avgCompletionTime' => isset($completionTimes[$userId]) 
                    ? $completionTimes[$userId]['sum'] / $completionTimes[$userId]['count'] 
                    : null,
                'avgPriority' => isset($prioritySums[$userId]) 
                    ? $prioritySums[$userId] / $priorityCounts[$userId] 
                    : null
            ];
        }
        
        // Merge the results
        foreach ($results as &$result) {
            $userId = $result['userId'];
            $result['avgCompletionTime'] = $avgMap[$userId]['avgCompletionTime'] ?? null;
            $result['avgPriority'] = $avgMap[$userId]['avgPriority'] ?? null;
        }
        
        return $results;
    }

    public function save(MaintenanceTask $entity, bool $flush = false): void
    {
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaintenanceTask $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPerformanceSummary(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // First, check if there are any tasks in the date range
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
            
        error_log(sprintf('Found %d tasks between %s and %s', $count, $from->format('Y-m-d'), $to->format('Y-m-d')));
        
        if ($count === 0) {
            return [];
        }
        
        // Then try a simpler query first to get all users with tasks
        $simpleQuery = $this->createQueryBuilder('t')
            ->select('u.id as userId, u.apellido, u.nombre, COUNT(t.id) as taskCount')
            ->leftJoin('t.assignedTo', 'u')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->groupBy('u.id')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery();
            
        $simpleResult = $simpleQuery->getArrayResult();
        error_log('Simple Query Result: ' . print_r($simpleResult, true));
        
        // Initialize result array with default values for all users
        $result = [];
        foreach ($simpleResult as $user) {
            $result[$user['userId']] = [
                'userId' => $user['userId'],
                'apellido' => $user['apellido'],
                'nombre' => $user['nombre'],
                'assignedCount' => 0,
                'completedCount' => 0,
                'inProgressCount' => 0,
                'avgMinutes' => 0
            ];
        }
        
        // Try to get detailed statistics
        try {
            $query = $this->createQueryBuilder('t')
                ->select('u.id as userId, u.apellido, u.nombre,
                    COUNT(t.id) as assignedCount,
                    SUM(CASE WHEN t.status = :completed THEN 1 ELSE 0 END) as completedCount,
                    SUM(CASE WHEN t.status = :inProgress THEN 1 ELSE 0 END) as inProgressCount,
                    COALESCE(AVG(
                        CASE 
                            WHEN t.completedAt IS NOT NULL AND t.startedAt IS NOT NULL THEN 
                                TIMESTAMPDIFF(
                                    \'MINUTE\',
                                    t.startedAt,
                                    t.completedAt
                                )
                            WHEN t.completedAt IS NOT NULL THEN 
                                TIMESTAMPDIFF(
                                    \'MINUTE\',
                                    t.createdAt,
                                    t.completedAt
                                )
                            ELSE NULL
                        END
                    ), 0) as avgMinutes')
                ->leftJoin('t.assignedTo', 'u')
                ->andWhere('t.createdAt BETWEEN :from AND :to')
                ->groupBy('u.id')
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->setParameter('completed', MaintenanceTask::STATUS_COMPLETED)
                ->setParameter('inProgress', MaintenanceTask::STATUS_IN_PROGRESS)
                ->getQuery();

            $detailedResults = $query->getArrayResult();
            error_log('Detailed Query Result: ' . print_r($detailedResults, true));
            
            // Merge detailed results with default values
            foreach ($detailedResults as $row) {
                if (isset($result[$row['userId']])) {
                    $result[$row['userId']] = array_merge($result[$row['userId']], $row);
                } else {
                    $result[$row['userId']] = array_merge([
                        'assignedCount' => 0,
                        'completedCount' => 0,
                        'inProgressCount' => 0,
                        'avgMinutes' => 0
                    ], $row);
                }
            }
            
            return array_values($result);
            
        } catch (\Exception $e) {
            error_log('Error in performance query: ' . $e->getMessage());
            
            // If detailed query fails, ensure we have all required fields in the simple result
            $formattedResults = [];
            foreach ($simpleResult as $row) {
                $formattedResults[] = [
                    'userId' => $row['userId'],
                    'apellido' => $row['apellido'],
                    'nombre' => $row['nombre'],
                    'assignedCount' => $row['taskCount'] ?? 0,
                    'completedCount' => 0,
                    'inProgressCount' => 0,
                    'avgMinutes' => 0
                ];
            }
            
            return $formattedResults;
        }
    }

    public function getUserPerformance(User $user, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        // Get maintenance tasks
        $maintenanceTasks = $this->createQueryBuilder('t')
            ->select(
                't.id', 
                't.title as description', 
                't.status', 
                't.createdAt', 
                't.scheduledDate as startedAt', 
                't.completedAt',
                't.priority',
                'c.name as category',
                't.actualDuration',
                't.updatedAt',
                "'maintenance' as type"
            )
            ->leftJoin('t.category', 'c')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        // Get ticket-related tasks
        $ticketTasks = $this->entityManager->createQueryBuilder()
            ->select(
                't.id', 
                't.title as description', 
                't.status', 
                't.createdAt', 
                't.createdAt as startedAt',  // Using createdAt as startedAt since startedAt doesn't exist
                't.takenAt as completedAt',  // Using takenAt as completedAt
                't.priority',
                'NULLIF(1,1) as category',  // Returns NULL for category since Ticket doesn't have one
                'NULLIF(1,1) as assignedAt',  // Using NULL since assignedAt doesn't exist
                'NULLIF(1,1) as actualDuration',  // Using NULL since actualDuration doesn't exist
                't.updatedAt',
                "'ticket' as type"
            )
            ->from('App\\Entity\\Ticket', 't')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        // Combine both result sets
        $tasks = array_merge($maintenanceTasks, $ticketTasks);

        // Sort by creation date (newest first)
        usort($tasks, function($a, $b) {
            return $b['createdAt'] <=> $a['createdAt'];
        });

        // Calculate duration in PHP for better compatibility
        foreach ($tasks as &$task) {
            $endDate = $task['completedAt'] ?? null;
            $startDate = $task['startedAt'] ?? $task['createdAt'];
            
            if ($endDate && $startDate) {
                $task['durationMin'] = (int)(($endDate->getTimestamp() - $startDate->getTimestamp()) / 60);
            } else {
                $task['durationMin'] = null;
            }
            
            // Ensure all expected fields exist
            $task['category'] = $task['category'] ?? null;
            $task['priority'] = $task['priority'] ?? 'medium';
        }

        return $tasks;
    }

    public function findByDateRange(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $showCompleted = true,
        ?int $categoryId = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.scheduledDate BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d 00:00:00'))
            ->setParameter('end', $end->format('Y-m-d 23:59:59'))
            ->orderBy('t.scheduledDate', 'ASC');

        if (!$showCompleted) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', 'completed');
        }

        if ($categoryId) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findTasksForCalendar(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $showCompleted = true,
        ?MaintenanceCategory $category = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.assignedTo', 'u')
            ->where('t.scheduledDate BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d 00:00:00'))
            ->setParameter('end', $end->format('Y-m-d 23:59:59'))
            ->orderBy('t.scheduledDate', 'ASC');

        if (!$showCompleted) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', MaintenanceTask::STATUS_COMPLETED);
        }

if ($category) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function findUpcomingTasks(\DateTimeInterface $date = null, int $limit = 10): array
    {
        $date = $date ?? new \DateTimeImmutable('now');

        return $this->createQueryBuilder('t')
            ->andWhere('t.dueDate >= :now')
            ->andWhere('t.status != :completed')
            ->setParameter('now', $date)
            ->setParameter('completed', MaintenanceTask::STATUS_COMPLETED)
            ->orderBy('t.dueDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getUserTaskStatusDistribution(User $user, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->where('t.assignedTo = :user')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('user', $user)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        // Initialize all statuses with 0 using the correct constants
        $statuses = [
            MaintenanceTask::STATUS_PENDING => 0,
            MaintenanceTask::STATUS_IN_PROGRESS => 0,
            MaintenanceTask::STATUS_COMPLETED => 0,
            MaintenanceTask::STATUS_OVERDUE => 0,
            MaintenanceTask::STATUS_SKIPPED => 0,
        ];

        // Update with actual counts
        foreach ($result as $row) {
            $statuses[$row['status']] = (int) $row['count'];
        }

        return [
            'labels' => array_keys($statuses),
            'data' => array_values($statuses),
            'colors' => [
                '#4E73DF', // Pending
                '#36B9CC', // In Progress
                '#1CC88A', // Completed
                '#F6C23E', // Overdue (yellow)
                '#6F42C1', // Skipped (purple)
            ]
        ];
    }

    public function findOverdueTasks(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.scheduledDate < :now')
            ->andWhere('t.status != :completed')
            ->andWhere('t.status != :overdue')
            ->setParameter('now', new \DateTime())
            ->setParameter('completed', MaintenanceTask::STATUS_COMPLETED)
            ->setParameter('overdue', MaintenanceTask::STATUS_OVERDUE)
            ->orderBy('t.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAssignedToUser(User $user, array $statuses = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.scheduledDate', 'ASC');

        if (!empty($statuses)) {
            $qb->andWhere('t.status IN (:statuses)')
               ->setParameter('statuses', $statuses);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCategory(int $categoryId, array $filters = [])
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.category = :categoryId')
            ->setParameter('categoryId', $categoryId);

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

if (!empty($filters['dateFrom'])) {
            $qb->andWhere('t.scheduledDate >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('t.scheduledDate <= :dateTo')
               ->setParameter('dateTo', $filters['dateTo']);
        }

        $sortField = $filters['sort'] ?? 'scheduledDate';
        $sortOrder = $filters['order'] ?? 'ASC';
        
        $qb->orderBy('t.' . $sortField, $sortOrder);

        return $qb->getQuery()->getResult();
    }

    public function findByFilters(array $filters = [])
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.assignedTo', 'u');

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

if (!empty($filters['category'])) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['assigned_to'])) {
            $qb->andWhere('t.assignedTo = :assignedTo')
               ->setParameter('assignedTo', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'] instanceof \DateTimeInterface 
                ? $filters['date_from'] 
                : new \DateTime($filters['date_from']);
            $qb->andWhere('t.scheduledDate >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom->format('Y-m-d 00:00:00'));
        }

        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'] instanceof \DateTimeInterface
                ? $filters['date_to']
                : new \DateTime($filters['date_to']);
            $qb->andWhere('t.scheduledDate <= :dateTo')
               ->setParameter('dateTo', $dateTo->format('Y-m-d 23:59:59'));
        }

        $sortField = $filters['sort'] ?? 'scheduledDate';
        $sortOrder = $filters['order'] ?? 'ASC';
        
        // Ensure the sort field has the proper table alias
        $validSortFields = [
            'id', 'title', 'description', 'status', 'scheduledDate',
            'completedAt', 'createdAt', 'updatedAt'
        ];
        
        if (in_array($sortField, $validSortFields)) {
            $qb->orderBy('t.' . $sortField, $sortOrder);
        } else {
            // Default sorting if invalid field provided
            $qb->orderBy('t.scheduledDate', $sortOrder);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTaskStats(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Set default date range to last 30 days if not specified
        $now = new \DateTime();
        $startDate = $startDate ?? (clone $now)->modify('-30 days');
        $endDate = $endDate ?? $now;
        
        // Base statistics query
        $sql = 'SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = :pending THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = :in_progress THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = :completed THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = :overdue OR (status = :pending2 AND scheduled_date < :now) THEN 1 ELSE 0 END) as overdue_tasks,
                COUNT(DISTINCT assigned_to_id) as active_users,
                AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_completion_hours
            FROM maintenance_task
            WHERE created_at BETWEEN :start_date AND :end_date';
        
        $stmt = $conn->prepare($sql);
        $baseStats = $stmt->executeQuery([
            'pending' => MaintenanceTask::STATUS_PENDING,
            'in_progress' => MaintenanceTask::STATUS_IN_PROGRESS,
            'completed' => MaintenanceTask::STATUS_COMPLETED,
            'overdue' => MaintenanceTask::STATUS_OVERDUE,
            'pending2' => MaintenanceTask::STATUS_PENDING,
            'now' => $now->format('Y-m-d H:i:s'),
            'start_date' => $startDate->format('Y-m-d 00:00:00'),
            'end_date' => $endDate->format('Y-m-d 23:59:59')
        ])->fetchAssociative();
        
        // Get category distribution
        $categorySql = 'SELECT 
                c.name as category_name,
                COUNT(t.id) as task_count,
                ROUND(COUNT(t.id) * 100.0 / (SELECT COUNT(*) FROM maintenance_task WHERE created_at BETWEEN :start_date AND :end_date), 1) as percentage
            FROM maintenance_task t
            LEFT JOIN maintenance_category c ON t.category_id = c.id
            WHERE t.created_at BETWEEN :start_date AND :end_date
            GROUP BY c.id, c.name
            ORDER BY task_count DESC';
            
        $categoryStats = $conn->executeQuery($categorySql, [
            'start_date' => $startDate->format('Y-m-d 00:00:00'),
            'end_date' => $endDate->format('Y-m-d 23:59:59')
        ])->fetchAllAssociative();
        
        // Get status trend (last 7 days)
        $trendStart = (clone $endDate)->modify('-6 days');
        $trendSql = 'SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = :completed THEN 1 ELSE 0 END) as completed
            FROM maintenance_task
            WHERE created_at BETWEEN :trend_start AND :end_date
            GROUP BY DATE(created_at)
            ORDER BY date ASC';
            
        $trendStats = $conn->executeQuery($trendSql, [
            'completed' => MaintenanceTask::STATUS_COMPLETED,
            'trend_start' => $trendStart->format('Y-m-d 00:00:00'),
            'end_date' => $endDate->format('Y-m-d 23:59:59')
        ])->fetchAllAssociative();
        
        // Calculate completion rate
        $completionRate = $baseStats['total_tasks'] > 0 
            ? round(($baseStats['completed_tasks'] / $baseStats['total_tasks']) * 100, 1)
            : 0;
            
        return [
            'overview' => [
                'total_tasks' => (int)($baseStats['total_tasks'] ?? 0),
                'pending_tasks' => (int)($baseStats['pending_tasks'] ?? 0),
                'in_progress_tasks' => (int)($baseStats['in_progress_tasks'] ?? 0),
                'completed_tasks' => (int)($baseStats['completed_tasks'] ?? 0),
                'overdue_tasks' => (int)($baseStats['overdue_tasks'] ?? 0),
                'active_users' => (int)($baseStats['active_users'] ?? 0),
                'avg_completion_hours' => round($baseStats['avg_completion_hours'] ?? 0, 1),
                'completion_rate' => $completionRate,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ],
            'categories' => $categoryStats,
            'trends' => $this->fillMissingDates($trendStats, $trendStart, $endDate)
        ];
    }
    
    private function fillMissingDates(array $data, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = [];
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate
        );
        
        $dataByDate = [];
        foreach ($data as $row) {
            $dataByDate[$row['date']] = $row;
        }
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            if (isset($dataByDate[$dateStr])) {
                $result[] = $dataByDate[$dateStr];
            } else {
                $result[] = [
                    'date' => $dateStr,
                    'total' => 0,
                    'completed' => 0
                ];
            }
        }
        
        return $result;
    }
}
