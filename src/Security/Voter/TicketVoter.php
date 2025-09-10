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
    public const PROPOSE_STATUS = 'propose_status';
    public const REJECT = 'reject';
    public const COMPLETE = 'complete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::NOTE, self::PROPOSE_STATUS, self::REJECT, self::COMPLETE])) {
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
            case self::PROPOSE_STATUS:
                return $this->canProposeStatus($ticket, $user);
            case self::REJECT:
                return $this->canReject($ticket, $user);
            case self::COMPLETE:
                return $this->canComplete($ticket, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        // Admins and Auditors can view any ticket
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_AUDITOR')) {
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
    
    private function canProposeStatus(Ticket $ticket, User $user): bool
    {
        // Any authenticated user can propose a status change to a ticket they can view
        return $this->canView($ticket, $user);
    }
    
    private function canReject(Ticket $ticket, User $user): bool
    {
        // Only admins and auditors can reject tickets
        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_AUDITOR')) {
            return false;
        }
        
        // Can only reject tickets that are not already completed or rejected
        return $ticket->getStatus() !== 'completed' && 
               $ticket->getStatus() !== 'rejected';
    }
    
    private function canComplete(Ticket $ticket, User $user): bool
    {
        // Only admins and auditors can complete tickets
        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_AUDITOR')) {
            return false;
        }
        
        // Can only complete tickets that are not already completed or rejected
        return $ticket->getStatus() !== 'completed' && 
               $ticket->getStatus() !== 'rejected';
    }
}
