<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\NoteReadStatus;

/**
 * @extends ServiceEntityRepository<NoteReadStatus>
 */
class NoteReadStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteReadStatus::class);
    }

    public function markAllNotesAsRead(Note $note, User $user): void
    {
        // Check if read status already exists
        $readStatus = $this->findOneBy(['note' => $note, 'user' => $user]);
        
        if (!$readStatus) {
            $readStatus = new NoteReadStatus();
            $readStatus->setNote($note);
            $readStatus->setUser($user);
            $this->getEntityManager()->persist($readStatus);
        }
        
        $readStatus->setIsRead(true);
        $this->getEntityManager()->flush();
    }
}
