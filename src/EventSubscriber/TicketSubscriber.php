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
        $changeDetected = false;

        // Check each field that changed
        foreach ($args->getEntityChangeSet() as $field => $change) {
            // Skip these fields as they're handled separately
            if (in_array($field, ['updatedAt', 'takenAt'])) {
                continue;
            }

            $oldValue = $change[0];
            $newValue = $change[1];

            // Handle special cases for related entities
            if ($field === 'takenBy') {
                $changes[] = [
                    'field' => 'takenBy',
                    'old' => $oldValue ? $oldValue->getEmail() : null,
                    'new' => $newValue ? $newValue->getEmail() : null,
                    'type' => 'user'
                ];
                $changeDetected = true;
                continue;
            }

            // Handle status changes
            if ($field === 'status') {
                $changes[] = [
                    'field' => 'status',
                    'old' => $oldValue,
                    'new' => $newValue,
                    'type' => 'status'
                ];
                $changeDetected = true;
                continue;
            }

            // Handle other fields
            if ($oldValue != $newValue) {
                $changes[] = [
                    'field' => $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                    'type' => 'field'
                ];
                $changeDetected = true;
            }
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
