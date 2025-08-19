<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\DBAL\Connection;
use App\Repository\RoleRepository;

#[Route('/admin')]
class UserController extends AbstractController
{
    #[Route('/users', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/metrics', name: 'admin_user_metrics', methods: ['GET'])]
    public function metrics(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all users
        $users = $userRepository->findAll();
        
        // Get login statistics for the last 30 days
        $loginStats = $this->getLoginStatistics();
        
        // Get user activity data
        $userActivity = $this->getUserActivityData($users);
        
        // Get activity distribution
        $activityDistribution = $this->getActivityDistribution();
        
        return $this->render('admin/user/metrics.html.twig', [
            'users' => $users,
            'login_dates' => $loginStats['dates'],
            'login_counts' => $loginStats['counts'],
            'user_activity' => $userActivity,
            'activity_labels' => array_keys($activityDistribution),
            'activity_data' => array_values($activityDistribution),
        ]);
    }
    
    private function getLoginStatistics(): array
    {
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify('-30 days');
        
        // Initialize dates array with 0 counts
        $dates = [];
        $counts = [];
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dates[] = $date->format('d M');
            $counts[$dateStr] = 0;
        }
        
        // Get login counts from the database
        $sql = "SELECT DATE(login_time) as login_date, COUNT(*) as count 
                FROM user_login_log 
                WHERE login_time >= :start_date 
                GROUP BY DATE(login_time)";
                
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery([
                'start_date' => $startDate->format('Y-m-d 00:00:00'),
            ]);
            
            $loginData = $result->fetchAllAssociative();
            
            // Update counts with actual data
            foreach ($loginData as $row) {
                $dateStr = (new \DateTime($row['login_date']))->format('Y-m-d');
                if (isset($counts[$dateStr])) {
                    $counts[$dateStr] = (int)$row['count'];
                }
            }
        } catch (\Exception $e) {
            // Log error or handle it as needed
        }
        
        // Convert to simple array of counts in date order
        $counts = array_values($counts);
        
        return [
            'dates' => $dates,
            'counts' => $counts,
        ];
    }
    
    private function getUserActivityData(array $users): array
    {
        $activity = [];
        
        // This is a simplified example - you would need to implement your own logic
        // to track user activities in your application
        foreach ($users as $user) {
            $lastLogin = method_exists($user, 'getLastLogin') ? $user->getLastLogin() : null;
            
            $activity[$user->getId()] = [
                'action' => 'Última acción', // Replace with actual action
                'timestamp' => $lastLogin ?? new \DateTime(),
                'totalActions' => 0, // Initialize with 0 instead of random for now
            ];
        }
        
        return $activity;
    }
    
    private function getActivityDistribution(): array
    {
        // This is a simplified example - replace with actual data from your application
        return [
            'Inicios de sesión' => 45,
            'Creaciones' => 32,
            'Actualizaciones' => 15,
            'Eliminaciones' => 5,
            'Otras acciones' => 12,
        ];
    }

    #[Route('/user/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Collect all form errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Error en el formulario: ' . implode(', ', $errors));
            } else {
                try {
                    // Set the password if provided
                    if ($plainPassword = $form->get('plainPassword')->getData()) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $plainPassword)
                        );
                    }
                    
                    // Handle roles - The form now handles the userRoles relationship directly
                    
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Usuario creado correctamente.');
                    return $this->redirectToRoute('admin_user_index');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al guardar el usuario: ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Collect all form errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Error en el formulario: ' . implode(', ', $errors));
            } else {
                try {
                    // Update password if a new one was entered
                    if ($plainPassword = $form->get('plainPassword')->getData()) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $plainPassword)
                        );
                    }
                    
                    // The form now handles the userRoles relationship directly
                    
                    $entityManager->flush();

                    $this->addFlash('success', 'Usuario actualizado correctamente.');
                    return $this->redirectToRoute('admin_user_index');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al actualizar el usuario: ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Usuario eliminado exitosamente');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
