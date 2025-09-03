<?php

namespace App\Repository;

use App\Entity\MaintenanceTask;
use App\Entity\MaintenanceCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceTask::class);
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

    public function findUpcomingTasks(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.scheduledDate >= :now')
            ->andWhere('t.status != :completed')
            ->setParameter('now', new \DateTime())
            ->setParameter('completed', MaintenanceTask::STATUS_COMPLETED)
            ->orderBy('t.scheduledDate', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
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
