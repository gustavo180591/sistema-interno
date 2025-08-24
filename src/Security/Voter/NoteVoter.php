<?php

namespace App\Security\Voter;

use App\Entity\Note;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class NoteVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Note) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // the user must be logged in
        if (!$user instanceof User) {
            return false;
        }

        /** @var Note $note */
        $note = $subject;

        switch ($attribute) {
            case self::VIEW:
                // Users can view notes if they can view the ticket
                return $this->security->isGranted('view', $note->getTicket());
            case self::EDIT:
                // Users can edit their own notes or if they have ROLE_ADMIN/ROLE_AUDITOR
                return ($user === $note->getCreatedBy()) || 
                       $this->security->isGranted('ROLE_ADMIN') || 
                       $this->security->isGranted('ROLE_AUDITOR');
            case self::DELETE:
                // Users can delete their own notes, and admins can delete any note
                return ($user === $note->getCreatedBy()) || $this->security->isGranted('ROLE_ADMIN');
        }

        throw new \LogicException('This code should not be reached!');
    }
}
