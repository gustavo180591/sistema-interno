<?php

namespace App\Repository;

use App\Entity\MaintenanceLog;
use App\Entity\MaintenanceTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceLog>
 *
 * @method MaintenanceLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaintenanceLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaintenanceLog[]    findAll()
 * @method MaintenanceLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaintenanceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceLog::class);
    }

    public function save(MaintenanceLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaintenanceLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTask(MaintenanceTask $task, int $limit = null, string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.task = :task')
            ->setParameter('task', $task)
            ->orderBy('l.createdAt', $order);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findRecentActivity(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.task', 't')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function logStatusChange(MaintenanceTask $task, string $oldStatus, string $newStatus, User $user): void
    {
        $log = new MaintenanceLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setType(MaintenanceLog::TYPE_STATUS_CHANGE);
        $log->setMessage('Cambió el estado de {oldStatus} a {newStatus}');
        $log->setDetails([
            'oldStatus' => $this->getStatusLabel($oldStatus),
            'newStatus' => $this->getStatusLabel($newStatus)
        ]);

        $this->save($log, true);
    }

    public function logAssignment(MaintenanceTask $task, ?User $assignedTo, User $user): void
    {
        $log = new MaintenanceLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setType(MaintenanceLog::TYPE_ASSIGNMENT);
        
        if ($assignedTo) {
            $log->setMessage('Asignó la tarea a {assignedTo}');
            $log->setDetails([
                'assignedTo' => $assignedTo->getNombreCompleto()
            ]);
        } else {
            $log->setMessage('Eliminó la asignación de la tarea');
        }

        $this->save($log, true);
    }

    public function logScheduleUpdate(MaintenanceTask $task, \DateTimeInterface $oldDate, \DateTimeInterface $newDate, User $user): void
    {
        $log = new MaintenanceLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setType(MaintenanceLog::TYPE_SCHEDULE_UPDATE);
        $log->setMessage('Actualizó la fecha programada de {oldDate} a {newDate}');
        $log->setDetails([
            'oldDate' => $oldDate->format('d/m/Y H:i'),
            'newDate' => $newDate->format('d/m/Y H:i')
        ]);

        $this->save($log, true);
    }

    public function logCompletion(MaintenanceTask $task, User $user): void
    {
        $log = new MaintenanceLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setType(MaintenanceLog::TYPE_COMPLETION);
        $log->setMessage('Marcó la tarea como completada');

        $this->save($log, true);
    }

    public function addComment(MaintenanceTask $task, string $comment, User $user): void
    {
        $log = new MaintenanceLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setType(MaintenanceLog::TYPE_COMMENT);
        $log->setMessage($comment);

        $this->save($log, true);
    }

    private function getStatusLabel(string $status): string
    {
        $statusLabels = [
            MaintenanceTask::STATUS_PENDING => 'Pendiente',
            MaintenanceTask::STATUS_IN_PROGRESS => 'En Progreso',
            MaintenanceTask::STATUS_COMPLETED => 'Completada',
            MaintenanceTask::STATUS_OVERDUE => 'Atrasada',
            MaintenanceTask::STATUS_SKIPPED => 'Omitida'
        ];

        return $statusLabels[$status] ?? $status;
    }
}
