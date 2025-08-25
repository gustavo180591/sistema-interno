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
            $qb->andWhere('t.scheduledDate >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('t.scheduledDate <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
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

    public function getTaskStats()
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = :pending THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = :in_progress THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = :completed THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = :overdue OR (status = :pending2 AND scheduled_date < :now) THEN 1 ELSE 0 END) as overdue_tasks
            FROM maintenance_task
        ';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'pending' => MaintenanceTask::STATUS_PENDING,
            'in_progress' => MaintenanceTask::STATUS_IN_PROGRESS,
            'completed' => MaintenanceTask::STATUS_COMPLETED,
            'overdue' => MaintenanceTask::STATUS_OVERDUE,
            'pending2' => MaintenanceTask::STATUS_PENDING,
            'now' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
        
        return $result->fetchAssociative();
    }
}
