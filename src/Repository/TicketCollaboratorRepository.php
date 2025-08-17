<?php

namespace App\Repository;

use App\Entity\TicketCollaborator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketCollaborator>
 */
class TicketCollaboratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketCollaborator::class);
    }

    public function findUserCollaboration($ticketId, $userId): ?TicketCollaborator
    {
        return $this->createQueryBuilder('tc')
            ->andWhere('tc.ticket = :ticketId')
            ->andWhere('tc.user = :userId')
            ->setParameter('ticketId', $ticketId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function addCollaborator($ticket, $user, $entityManager): void
    {
        $collaboration = new TicketCollaborator();
        $collaboration->setTicket($ticket);
        $collaboration->setUser($user);
        
        $entityManager->persist($collaboration);
    }
}
