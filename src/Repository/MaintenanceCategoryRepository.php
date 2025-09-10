<?php

namespace App\Repository;

use App\Entity\MaintenanceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceCategory>
 *
 * @method MaintenanceCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaintenanceCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaintenanceCategory[]    findAll()
 * @method MaintenanceCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaintenanceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceCategory::class);
    }

    public function save(MaintenanceCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaintenanceCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithTaskCounts()
    {
        return $this->createQueryBuilder('c')
            ->select('c as category', 'COUNT(t.id) as taskCount')
            ->leftJoin('c.tasks', 't')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}
