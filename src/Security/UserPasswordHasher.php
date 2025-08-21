<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasher
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function hashPassword(User $user, string $plainPassword): string
    {
        return $this->hasher->hashPassword($user, $plainPassword);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->hasher->isPasswordValid($user, $plainPassword);
    }
}
