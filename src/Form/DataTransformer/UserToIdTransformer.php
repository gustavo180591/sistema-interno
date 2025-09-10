<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToIdTransformer implements DataTransformerInterface
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Transforms an array of User objects to an array of ids
     */
    public function transform($users): array
    {
        if (null === $users) {
            return [];
        }

        return array_map(function(User $user) {
            return (string)$user->getId();
        }, $users);
    }

    /**
     * Transforms an array of ids to an array of User objects
     */
    public function reverseTransform($userIds): array
    {
        if (!is_array($userIds)) {
            return [];
        }

        $userIds = array_filter(array_map('trim', $userIds), function($id) {
            return !empty($id);
        });

        if (empty($userIds)) {
            return [];
        }

        $users = $this->userRepository->findBy(['id' => $userIds]);

        if (count($users) !== count($userIds)) {
            throw new TransformationFailedException('One or more users could not be found');
        }

        return $users;
    }
}
