<?php

namespace App\Repository;

use App\Entity\Area;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Area|null find($id, $lockMode = null, $lockVersion = null)
 * @method Area|null findOneBy(array $criteria, array $orderBy = null)
 * @method Area[]    findAll()
 * @method Area[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Area::class);
    }

    public function findActivos()
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.activo = :val')
            ->setParameter('val', true)
            ->orderBy('a.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca áreas según el término de búsqueda y estado
     */
    public function search(string $search = null, bool $activo = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.nombre', 'ASC');

        if ($search) {
            $qb->andWhere('a.nombre LIKE :search OR a.descripcion LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($activo !== null) {
            $qb->andWhere('a.activo = :activo')
               ->setParameter('activo', $activo);
        }

        return $qb->getQuery()->getResult();
    }
}
