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
                    // Set password if provided
                    $plainPassword = $form->get('plainPassword')->getData();
                    if ($plainPassword) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $plainPassword)
                        );
                    }
                    
                    // Handle roles
                    $roles = $form->get('roles')->getData();
                    if (empty($roles)) {
                        $roles = ['ROLE_USER'];
                    }
                    $user->setRoles($roles);
                    
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
                    // Update password if provided
                    $plainPassword = $form->get('plainPassword')->getData();
                    if ($plainPassword) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $plainPassword)
                        );
                    }
                    
                    // Handle roles
                    $roles = $form->get('roles')->getData();
                    if (empty($roles)) {
                        $roles = ['ROLE_USER'];
                    }
                    $user->setRoles($roles);
                    
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
