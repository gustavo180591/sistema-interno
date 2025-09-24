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
     * Find paginated tickets with filters
     *
     * @param int $page
     * @param int $limit
     * @param string $sort
     * @param string $direction
     * @param array $filters
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    /**
     * Get a query builder for paginated tickets with filters
     *
     * @param string $sort
     * @param string $direction
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findPaginated(string $sort, string $direction, array $filters = [])
    {
        // Create base query builder with all necessary joins
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.createdBy', 'createdBy')
            ->leftJoin('t.ticketAssignments', 'ta')
            ->leftJoin('ta.user', 'assignedUser')
            ->where('t.status != :archived')
            ->setParameter('archived', 'archived');
            
        // Apply filters
        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }
        
        if (!empty($filters['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':search'),
                    $qb->expr()->like('t.description', ':search'),
                    $qb->expr()->like('t.idSistemaInterno', ':search')
                )
            )->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['area'])) {
            $qb->andWhere('t.area_origen = :area')
               ->setParameter('area', $filters['area']);
        }
        
        // Apply access control
        if (empty($filters['isAdmin']) && isset($filters['user'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('t.createdBy', ':currentUser'),
                    $qb->expr()->eq('assignedUser', ':currentUser')
                )
            )->setParameter('currentUser', $filters['user']);
        }
        
        // Apply sorting
        if ($sort === 'createdBy') {
            $qb->orderBy('createdBy.apellido', $direction);
        } else {
            $qb->orderBy('t.' . $sort, $direction);
        }
        
        return $qb;
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
            ->select('t.area_origen as area, COUNT(t.id) as count')
            ->groupBy('t.area_origen')
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
