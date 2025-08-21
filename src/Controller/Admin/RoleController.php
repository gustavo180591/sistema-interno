<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/roles')]
class RoleController extends AbstractController
{
    #[Route('/', name: 'admin_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository, EntityManagerInterface $entityManager): Response
    {
        // Ensure we have the default roles first
        $this->ensureDefaultRolesExist($roleRepository, $entityManager);
        
        // Get all roles, ordered by name
        $roles = $roleRepository->createQueryBuilder('r')
            ->orderBy('r.roleName', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/role/index.html.twig', [
            'roles' => $roles,
        ]);
    }

    /**
     * Ensure that default roles exist in the database
     */
    private function ensureDefaultRolesExist(RoleRepository $roleRepository, EntityManagerInterface $entityManager): void
    {
        $defaultRoles = [
            [
                'name' => 'Administrador',
                'roleName' => 'ROLE_ADMIN'
            ],
            [
                'name' => 'Usuario',
                'roleName' => 'ROLE_USER'
            ],
            [
                'name' => 'Auditor',
                'roleName' => 'ROLE_AUDITOR',
                'description' => 'Rol con permisos de solo lectura para auditar el sistema'
            ]
        ];

        foreach ($defaultRoles as $roleData) {
            $role = $roleRepository->findOneBy(['roleName' => $roleData['roleName']]);
            
            if (!$role) {
                $role = new Role();
                $role->setName($roleData['name']);
                $role->setRoleName($roleData['roleName']);
                // Set empty description if not provided
                $description = $roleData['description'] ?? '';
                $role->setDescription($description);
                $role->setCreatedAt(new \DateTime());
                
                $entityManager->persist($role);
            }
        }
        
        $entityManager->flush();
    }

    #[Route('/new', name: 'admin_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rol creado exitosamente.');
            return $this->redirectToRoute('admin_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/role/new.html.twig', [
            'role' => $role,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_role_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, RoleRepository $roleRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $role = $roleRepository->find($id);
        
        if (!$role) {
            $this->addFlash('error', 'El rol solicitado no existe.');
            return $this->redirectToRoute('admin_role_index');
        }
        
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Rol actualizado exitosamente.');
            return $this->redirectToRoute('admin_role_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/role/edit.html.twig', [
            'role' => $role,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_role_delete', methods: ['POST'])]
    public function delete(Request $request, RoleRepository $roleRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $role = $roleRepository->find($id);
        
        if (!$role) {
            $this->addFlash('error', 'El rol solicitado no existe.');
            return $this->redirectToRoute('admin_role_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $entityManager->remove($role);
            $entityManager->flush();
            $this->addFlash('success', 'Rol eliminado exitosamente.');
        }

        return $this->redirectToRoute('admin_role_index', [], Response::HTTP_SEE_OTHER);
    }
}
