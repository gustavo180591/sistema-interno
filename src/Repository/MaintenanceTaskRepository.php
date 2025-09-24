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

    // Detalle de un usuario (cálculo en PHP para mayor compatibilidad)
    public function getUserPerformance(int $userId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.assignedTo', 'u')
            ->addSelect('u')
            ->where('u.id = :uid')
            ->andWhere('t.completedAt BETWEEN :f AND :t')
            ->setParameter('uid', $userId)
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        /** @var MaintenanceTask[] $tasks */
        $tasks = $qb->getQuery()->getResult();

        $cerr = 0;
        $sumHours = 0.0;
        $slaTotal = 0;
        $slaMet = 0;
        $reab = 0;
        $username = null; $nombre = null; $apellido = null;

        foreach ($tasks as $task) {
            $user = $task->getAssignedTo();
            if ($user instanceof User) {
                $username = method_exists($user, 'getUsername') ? $user->getUsername() : ($user->getEmail() ?? $username);
                $nombre = method_exists($user, 'getNombre') ? $user->getNombre() : $nombre;
                $apellido = method_exists($user, 'getApellido') ? $user->getApellido() : $apellido;
            }

            $createdAt = $task->getCreatedAt();
            $completedAt = $task->getCompletedAt();
            if ($createdAt instanceof \DateTimeInterface && $completedAt instanceof \DateTimeInterface) {
                $cerr++;
                $diffSeconds = max(0, $completedAt->getTimestamp() - $createdAt->getTimestamp());
                $hours = $diffSeconds / 3600.0;
                $sumHours += $hours;
                $slaTotal++;
                if ($hours <= 48.0) { $slaMet++; }
            }
            if ($task->isReopened()) { $reab++; }
        }

        $tmr = $cerr > 0 ? ($sumHours / $cerr) : 0.0;
        $slaPct = $slaTotal > 0 ? (100.0 * $slaMet / $slaTotal) : 0.0;
        $reabPct = $cerr > 0 ? (100.0 * $reab / $cerr) : 0.0;

        return [
            'userId' => $userId,
            'username' => $username,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'cerrados' => $cerr,
            'tmrHoras' => $tmr,
            'slaPct' => $slaPct,
            'reabPct' => $reabPct,
        ];
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

    /**
     * Find tasks for calendar view within a date range.
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param bool $showCompleted Include completed tasks if true
     * @param MaintenanceCategory|null $category Filter by category if provided
     * @return MaintenanceTask[]
     */
    public function findTasksForCalendar(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $showCompleted = true,
        ?MaintenanceCategory $category = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->where('t.scheduledDate BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.scheduledDate', 'ASC');

        if (!$showCompleted) {
            $qb->andWhere('t.status != :completed')
               ->setParameter('completed', 'completed');
        }

        if ($category !== null) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Portable summary computed in PHP for any database engine.
     * Returns keys: totalCerrados, tmrHoras, reabiertos.
     */
    public function getPerformanceSummaryPhp(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.completedAt BETWEEN :f AND :t')
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        /** @var MaintenanceTask[] $tasks */
        $tasks = $qb->getQuery()->getResult();

        $totalCerrados = 0;
        $sumHours = 0.0;
        $reabiertos = 0;

        foreach ($tasks as $task) {
            $completedAt = $task->getCompletedAt();
            $createdAt = $task->getCreatedAt();
            if ($completedAt instanceof \DateTimeInterface && $createdAt instanceof \DateTimeInterface) {
                $totalCerrados++;
                $diffSeconds = max(0, $completedAt->getTimestamp() - $createdAt->getTimestamp());
                $sumHours += $diffSeconds / 3600.0;
            }
            if ($task->isReopened()) {
                $reabiertos++;
            }
        }

        $tmrHoras = $totalCerrados > 0 ? $sumHours / $totalCerrados : 0.0;

        return [
            'totalCerrados' => $totalCerrados,
            'tmrHoras' => $tmrHoras,
            'reabiertos' => $reabiertos,
        ];
    }

    /**
     * Portable per-user performance rows computed in PHP.
     * Each row contains: userId, username, nombre, apellido, cerrados, tmrHoras, slaPct, reabPct.
     */
    public function getAssignedUsersPerformancePhp(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.assignedTo', 'u')
            ->addSelect('u')
            ->andWhere('t.completedAt BETWEEN :f AND :t')
            ->andWhere('t.assignedTo IS NOT NULL')
            ->setParameter('f', $from)
            ->setParameter('t', $to);

        /** @var MaintenanceTask[] $tasks */
        $tasks = $qb->getQuery()->getResult();

        // Aggregate per user
        $agg = [];
        foreach ($tasks as $task) {
            $user = $task->getAssignedTo();
            if (!$user instanceof User) { continue; }
            $uid = (int) $user->getId();
            if (!isset($agg[$uid])) {
                $agg[$uid] = [
                    'userId' => $uid,
                    'username' => method_exists($user, 'getUsername') ? $user->getUsername() : ($user->getEmail() ?? ''),
                    'nombre' => method_exists($user, 'getNombre') ? $user->getNombre() : null,
                    'apellido' => method_exists($user, 'getApellido') ? $user->getApellido() : null,
                    'cerrados' => 0,
                    'sumHours' => 0.0,
                    'slaTotal' => 0,
                    'slaMet' => 0,
                    'reabiertos' => 0,
                ];
            }

            $agg[$uid]['cerrados']++;

            $createdAt = $task->getCreatedAt();
            $completedAt = $task->getCompletedAt();
            if ($createdAt instanceof \DateTimeInterface && $completedAt instanceof \DateTimeInterface) {
                $diffSeconds = max(0, $completedAt->getTimestamp() - $createdAt->getTimestamp());
                $hours = $diffSeconds / 3600.0;
                $agg[$uid]['sumHours'] += $hours;

                // SLA simple 48h
                $agg[$uid]['slaTotal']++;
                if ($hours <= 48.0) { $agg[$uid]['slaMet']++; }
            }

            if ($task->isReopened()) {
                $agg[$uid]['reabiertos']++;
            }
        }

        // Finalize rows
        $rows = [];
        foreach ($agg as $row) {
            $cerr = max(0, (int) $row['cerrados']);
            $tmr = $cerr > 0 ? ($row['sumHours'] / $cerr) : 0.0;
            $slaPct = $row['slaTotal'] > 0 ? (100.0 * $row['slaMet'] / $row['slaTotal']) : 0.0;
            $reabPct = $cerr > 0 ? (100.0 * $row['reabiertos'] / $cerr) : 0.0;

            $rows[] = [
                'userId' => $row['userId'],
                'username' => $row['username'],
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'cerrados' => $cerr,
                'tmrHoras' => $tmr,
                'slaPct' => $slaPct,
                'reabPct' => $reabPct,
            ];
        }

        return $rows;
    }
}
