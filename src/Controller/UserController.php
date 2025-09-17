<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/admin/api/users', name: 'app_api_users', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUsersApi(UserRepository $userRepository): JsonResponse
    {
        try {
            // Get the current user
            $currentUser = $this->getUser();
            
            if (!$currentUser) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Authentication required'
                ], 401);
            }
            
            // Get all active users
            $users = $userRepository->findBy(['isActive' => true]);
            
            $usersArray = array_map(function(User $user) use ($currentUser) {
                return [
                    'id' => $user->getId(),
                    'name' => trim($user->getNombre() . ' ' . $user->getApellido()),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isCurrentUser' => $currentUser->getId() === $user->getId()
                ];
            }, $users);

            // Sort users by name
            usort($usersArray, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $this->json([
                'status' => 'success',
                'data' => $usersArray
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Error retrieving users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
