<?php

namespace App\Repository;

use App\Entity\MaintenanceTask;
use App\Entity\User;
use App\Entity\MaintenanceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceTask>
 */
class MaintenanceTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceTask::class);
    }

    // KPI por usuario (volumen, TMR, SLA, %reabiertos, etc.)
    public function getAssignedUsersPerformance(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('u.id AS userId, u.username, u.nombre, u.apellido')
            ->addSelect('COUNT(t.id) AS cerrados')
            ->addSelect('AVG(TIMESTAMPDIFF(HOUR, t.createdAt, t.completedAt)) AS tmrHoras')
            ->addSelect('100.0 * SUM(CASE WHEN t.withinSla = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id),0) AS slaPct')
            ->addSelect('100.0 * SUM(CASE WHEN t.reopened = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id),0) AS reabPct')
            ->leftJoin('t.assignedTo', 'u')
            ->where('t.completedAt BETWEEN :f AND :t')
            ->andWhere('t.assignedTo IS NOT NULL')
            ->groupBy('u.id')
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        return $qb->getQuery()->getArrayResult();
    }

    // Totales del período (para cards del dashboard)
    public function getPerformanceSummary(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS totalCerrados')
            ->addSelect('AVG(TIMESTAMPDIFF(HOUR, t.createdAt, t.completedAt)) AS tmrHoras')
            ->addSelect('100.0 * SUM(CASE WHEN t.withinSla = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id),0) AS slaPct')
            ->where('t.completedAt BETWEEN :f AND :t')
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        return (array) $qb->getQuery()->getSingleResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    // Detalle de un usuario
    public function getUserPerformance(int $userId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('u as user')
            ->addSelect('COUNT(t.id) AS cerrados')
            ->addSelect('AVG(TIMESTAMPDIFF(HOUR, t.createdAt, t.completedAt)) AS tmrHoras')
            ->addSelect('100.0 * SUM(CASE WHEN t.withinSla = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id),0) AS slaPct')
            ->addSelect('100.0 * SUM(CASE WHEN t.reopened = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id),0) AS reabPct')
            ->leftJoin('t.assignedTo', 'u')
            ->where('u.id = :uid')
            ->andWhere('t.completedAt BETWEEN :f AND :t')
            ->groupBy('u.id')
            ->setParameter('uid', $userId)
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        return (array) $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY) ?: [];
    }

    // Tasa de finalización (opcional)
    public function calculateCompletionRate(\DateTimeInterface $from, \DateTimeInterface $to): float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 100.0 * SUM(CASE WHEN status = :done THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0) AS rate
            FROM maintenance_task
            WHERE created_at BETWEEN :f AND :t";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'done' => 'completed',
            'f' => $from->format('Y-m-d H:i:s'),
            't' => $to->format('Y-m-d H:i:s'),
        ])->fetchAssociative();

        return (float) ($result['rate'] ?? 0.0);
    }
    
    // Basic CRUD operations
    public function save(MaintenanceTask $entity, bool $flush = false): void
    {
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
    
    // Find tasks by date range
    public function findByDateRange(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $showCompleted = true,
        ?int $categoryId = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if (!$showCompleted) {
            $qb->andWhere('t.status != :status')
               ->setParameter('status', 'completed');
        }

        if ($categoryId !== null) {
            $qb->andWhere('t.category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }
    
    // Find overdue tasks
    public function findOverdueTasks(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('t')
            ->where('t.dueDate < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('now', $now)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getResult();
    }
    
    // Find tasks assigned to a specific user
    public function findAssignedToUser(User $user, array $statuses = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->setParameter('user', $user);
            
        if (!empty($statuses)) {
            $qb->andWhere('t.status IN (:statuses)')
               ->setParameter('statuses', $statuses);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    // Find tasks by category
    public function findByCategory(int $categoryId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.category = :categoryId')
            ->setParameter('categoryId', $categoryId);
            
        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }
        
        return $qb->getQuery()->getResult();
    }
}
