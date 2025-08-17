<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public const ITEMS_PER_PAGE = 15;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function search(
        ?string $search = null,
        ?string $estado = null,
        ?int $departamento = null,
        ?\DateTimeInterface $fechaDesde = null,
        ?\DateTimeInterface $fechaHasta = null,
        int $page = 1,
        int $itemsPerPage = self::ITEMS_PER_PAGE,
        string $sortBy = 'createdAt',
        string $sortOrder = 'DESC'
    ): array {
        // Validate sort field
        $validSortFields = ['id', 'ticketId', 'pedido', 'descripcion', 'departamento', 'estado', 'createdAt'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'createdAt';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.createdBy', 'u');
            
        // Apply sorting
        if ($sortBy === 'departamento') {
            $qb->addSelect("CASE t.departamento 
                WHEN 1 THEN 'Sistemas' 
                WHEN 2 THEN 'Administración' 
                WHEN 3 THEN 'Recursos Humanos' 
                WHEN 4 THEN 'Contabilidad' 
                WHEN 5 THEN 'Ventas' 
                WHEN 6 THEN 'Atención al Cliente' 
                WHEN 7 THEN 'Logística' 
                WHEN 8 THEN 'Almacén' 
                WHEN 9 THEN 'Compras' 
                WHEN 10 THEN 'Dirección' 
                ELSE 'Otro' 
            END AS HIDDEN deptName");
            $qb->orderBy('deptName', $sortOrder);
        } else {
            $qb->orderBy('t.' . $sortBy, $sortOrder);
        }

        if ($search) {
            $qb->andWhere('t.ticketId LIKE :search OR t.pedido LIKE :search OR t.descripcion LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($estado) {
            $qb->andWhere('t.estado = :estado')
               ->setParameter('estado', $estado);
        }

        if ($departamento) {
            $qb->andWhere('t.departamento = :departamento')
               ->setParameter('departamento', $departamento);
        }

        if ($fechaDesde) {
            $qb->andWhere('t.createdAt >= :fechaDesde')
               ->setParameter('fechaDesde', $fechaDesde->setTime(0, 0, 0));
        }

        if ($fechaHasta) {
            $qb->andWhere('t.createdAt <= :fechaHasta')
               ->setParameter('fechaHasta', $fechaHasta->setTime(23, 59, 59));
        }

        // Pagination
        $query = $qb->getQuery()
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $itemsPerPage);

        return [
            'items' => $paginator->getIterator(),
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => $pagesCount,
        ];
    }
}
