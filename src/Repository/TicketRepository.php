<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Count tickets by status
     *
     * @return array<string, int>
     */
    public function countTicketsByStatus(): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $counts = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'rejected' => 0
        ];

        foreach ($results as $result) {
            if (isset($counts[$result['status']])) {
                $counts[$result['status']] = (int) $result['count'];
            }
        }

        return $counts;
    }

    /**
     * Count tickets by area
     *
     * @return array<string, int>
     */
    public function countTicketsByArea(): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('t.areaOrigen as area, COUNT(t.id) as count')
            ->groupBy('t.areaOrigen')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $areaName = $result['area'] ?? 'Sin área';
            $counts[$areaName] = (int) $result['count'];
        }

        return $counts;
    }
}
