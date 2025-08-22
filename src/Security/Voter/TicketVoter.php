<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class TicketVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const NOTE = 'note';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::NOTE])) {
            return false;
        }

        // only vote on `Ticket` objects
        if (!$subject instanceof Ticket) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // the user must be logged in; if not, deny access
        if (!$user instanceof User) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($ticket, $user);
            case self::EDIT:
                return $this->canEdit($ticket, $user);
            case self::DELETE:
                return $this->canDelete($ticket, $user);
            case self::NOTE:
                return $this->canAddNote($ticket, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        // If they can edit, they can view
        if ($this->canEdit($ticket, $user)) {
            return true;
        }

        // The creator can always view
        if ($ticket->getCreatedBy() === $user) {
            return true;
        }

        // Check if user is assigned to the ticket
        foreach ($ticket->getTicketAssignments() as $assignment) {
            if ($assignment->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        // Admins can edit any ticket
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Auditors can edit tickets they created or are assigned to
        if ($this->security->isGranted('ROLE_AUDITOR')) {
            if ($ticket->getCreatedBy() === $user) {
                return true;
            }
            
            foreach ($ticket->getTicketAssignments() as $assignment) {
                if ($assignment->getUser() === $user) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canDelete(Ticket $ticket, User $user): bool
    {
        // Only admins can delete tickets
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canAddNote(Ticket $ticket, User $user): bool
    {
        // Any authenticated user can add a note to a ticket they can view
        return $this->canView($ticket, $user);
    }
}
