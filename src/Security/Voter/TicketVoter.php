<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Ticket) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($ticket, $user),
            self::EDIT => $this->canEdit($ticket, $user),
            self::DELETE => $this->canDelete($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        // El creador del ticket puede verlo
        if ($ticket->getCreatedBy() === $user) {
            return true;
        }

        // Los colaboradores pueden verlo
        foreach ($ticket->getCollaborators() as $collaborator) {
            if ($collaborator->getUser() === $user) {
                return true;
            }
        }

        // Los administradores pueden ver todos los tickets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return false;
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        // El creador del ticket puede editarlo
        if ($ticket->getCreatedBy() === $user) {
            return true;
        }

        // Los colaboradores pueden editarlo
        foreach ($ticket->getCollaborators() as $collaborator) {
            if ($collaborator->getUser() === $user) {
                return true;
            }
        }

        // Los administradores pueden editar todos los tickets
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return false;
    }

    private function canDelete(Ticket $ticket, User $user): bool
    {
        // Solo el creador o administradores pueden eliminar tickets
        if ($ticket->getCreatedBy() === $user) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return false;
    }
} 