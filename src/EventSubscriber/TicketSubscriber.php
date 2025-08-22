<?php

namespace App\EventSubscriber;

use App\Entity\Ticket;
use App\Entity\TicketUpdate;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Security;

class TicketSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Ticket) {
            return;
        }

        $user = $this->security->getUser();
        if ($user) {
            $entity->setCreatedBy($user);
            
            // Create initial update
            $update = new TicketUpdate();
            $update->setTicket($entity);
            $update->setUser($user);
            $update->setType('create');
            $entity->addUpdate($update);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Ticket) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $changes = [];

        // Track status changes
        if ($args->hasChangedField('status')) {
            $changes[] = [
                'field' => 'status',
                'old' => $args->getOldValue('status'),
                'new' => $args->getNewValue('status')
            ];
        }

        if ($changeDetected) {
            $update = new TicketUpdate();
            $update->setTicket($entity);
            $update->setUser($user);
            $update->setType('update');
            $update->setChanges($changes);
            
            $em = $args->getObjectManager();
            $em->persist($update);
            $em->flush($update);
        }
    }
}
