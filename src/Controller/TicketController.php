<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TicketController extends AbstractController
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_REJECTED = 'rejected';
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository
    ) {
    }
    
    #[Route('/tickets/{id}/update-status/{status?}', name: 'ticket_update_status', methods: ['POST'], priority: 10)]
    #[IsGranted('edit', 'ticket')]
    public function updateStatus(Request $request, Ticket $ticket, ?string $status = null): Response
    {
        // Handle the new route with status in URL
        if ($status !== null) {
            $validStatuses = [
                'rechazado' => self::STATUS_REJECTED,
                'en_progreso' => self::STATUS_IN_PROGRESS,
                'completado' => self::STATUS_COMPLETED
            ];
            
            if (!array_key_exists($status, $validStatuses)) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
            
            $newStatus = $validStatuses[$status];
        } else {
            // Handle the old route with status in request body
            $newStatus = $request->request->get('status');
            
            if (!in_array($newStatus, [
                self::STATUS_PENDING,
                self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED,
                self::STATUS_REJECTED
            ])) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
        }

        // Set the status
        $ticket->setStatus($newStatus);
        
        // If completing the ticket, set the completedAt timestamp
        if ($newStatus === self::STATUS_COMPLETED) {
            $ticket->setCompletedAt(new \DateTimeImmutable());
        }
        
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $statusMessage = [
            self::STATUS_PENDING => 'pendiente',
            self::STATUS_IN_PROGRESS => 'en progreso',
            self::STATUS_COMPLETED => 'completado',
            self::STATUS_REJECTED => 'rechazado'
        ][$newStatus] ?? 'actualizado';
        
        $this->addFlash('success', sprintf('El ticket ha sido marcado como %s', $statusMessage));
        
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }
        
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/tickets/{id}/edit-description', name: 'ticket_edit_description', methods: ['POST'])]
    #[IsGranted('edit', 'ticket')]
    public function editDescription(Ticket $ticket, Request $request): Response
    {
        $description = $request->request->get('description');
        
        if (empty(trim($description))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'error' => 'La descripción no puede estar vacía']);
            }
            $this->addFlash('error', 'La descripción no puede estar vacía');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        $ticket->setDescription($description);
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
        
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'description' => nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8'))
            ]);
        }
        
        $this->addFlash('success', 'La descripción ha sido actualizada');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets/{id}/take', name: 'ticket_take', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function takeTicket(Ticket $ticket, Request $request): Response
    {
        $user = $this->getUser();
        
        // Check if the ticket is already taken
        if ($ticket->getTakenBy()) {
            $this->addFlash('warning', 'Este ticket ya ha sido tomado por otro usuario.');
            return $this->redirectToRoute('homepage');
        }

        // Set the current user as the taker
        $ticket->setTakenBy($user);
        $ticket->setStatus(self::STATUS_IN_PROGRESS);
        
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets', name: 'ticket_index')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tickets = $this->isGranted('ROLE_ADMIN') 
            ? $entityManager->getRepository(Ticket::class)->findAll()
            : $entityManager->getRepository(Ticket::class)->findBy(['createdBy' => $this->getUser()]);
        $users = $entityManager->getRepository(User::class)->findAll();
        
        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'users' => $users,
        ]);
    }

    #[Route('/tickets/new', name: 'ticket_new')]
    public function new(Request $request): Response
    {
        // Check if user has AUDITOR role
        if (!$this->isGranted('ROLE_AUDITOR')) {
            $this->addFlash('error', 'No tienes permiso para crear tickets. Solo los usuarios con rol AUDITOR pueden crear nuevos tickets.');
            return $this->redirectToRoute('ticket_index');
        }
        
        $ticket = new Ticket();
        $ticket->setCreatedBy($this->getUser());
        
        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Ticket creado exitosamente.');
            return $this->redirectToRoute('ticket_index');
        }
        
        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/tickets/assign', name: 'ticket_assign', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function assign(Request $request, EntityManagerInterface $em, TicketRepository $tickets, UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUDITOR');
        $this->isCsrfTokenValid('assign_ticket', $request->request->get('_token')) || throw $this->createAccessDeniedException();

        $ticketId = $request->request->get('ticket_id');
        $userIds = $request->request->all('assigned_users');
        
        // Debug: Log the raw request data
        error_log('Raw request data: ' . print_r($request->request->all(), true));
        
        // Handle different formats of user IDs
        if (is_string($userIds)) {
            $userIds = [$userIds];
        } elseif (!is_array($userIds)) {
            $userIds = [];
        }
        
        // Filter out any empty values and convert to integers
        $userIds = array_filter(array_map('intval', $userIds), function($value) {
            return $value > 0;
        });
        
        // Debug: Log processed data
        error_log('Processed ticket_id: ' . $ticketId);
        error_log('Processed user IDs: ' . print_r($userIds, true));
        
        if (empty($userIds)) {
            $this->addFlash('danger', 'Debe seleccionar al menos un usuario para asignar el ticket.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }

        $ticket = $tickets->find($ticketId);
        if (!$ticket) {
            $this->addFlash('danger', 'Ticket no encontrado');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }
        
        // Check if ticket is already taken
        if ($ticket->getTakenBy()) {
            $this->addFlash('warning', 'No se puede asignar usuarios a un ticket que ya ha sido tomado.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }

        // Check if ticket is closed
        if ($ticket->getStatus() === self::STATUS_COMPLETED || $ticket->getStatus() === self::STATUS_REJECTED) {
            $this->addFlash('warning', 'No se pueden asignar usuarios a un ticket cerrado.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }

        try {
            // Debug: Log the raw user IDs from the request
            error_log('Raw user IDs from request: ' . print_r($userIds, true));
            
            // Convert string IDs to integers and filter out any invalid values
            $userIds = array_filter(array_map('intval', $userIds), function($id) {
                return $id > 0;
            });
            
            if (empty($userIds)) {
                throw new \Exception('No se proporcionaron IDs de usuario válidos.');
            }
            
            error_log('Processed user IDs: ' . print_r($userIds, true));
            
            // Get all users with the given IDs first
            $allUsers = $users->createQueryBuilder('u')
                ->where('u.id IN (:ids)')
                ->setParameter('ids', $userIds)
                ->getQuery()
                ->getResult();
                
            error_log('Found ' . count($allUsers) . ' users with the given IDs');
            
            // Filter users by role in PHP to ensure we see what's happening
            $validUsers = [];
            foreach ($allUsers as $user) {
                $roles = $user->getRoles();
                error_log(sprintf(
                    'User ID: %d, Roles: %s', 
                    $user->getId(), 
                    json_encode($roles)
                ));
                
                if (in_array('ROLE_USER', $roles) || in_array('ROLE_AUDITOR', $roles)) {
                    $validUsers[] = $user;
                }
            }
            
            error_log('Found ' . count($validUsers) . ' valid users after role filtering');

            if (empty($validUsers)) {
                throw new \Exception('No se encontraron usuarios válidos para asignar. Los usuarios deben tener el rol ROLE_USER o ROLE_AUDITOR.');
            }

            // Get current assignments to preserve timestamps
            $currentAssignments = $ticket->getTicketAssignments();
            $currentUserIds = [];
            foreach ($currentAssignments as $assignment) {
                $currentUserIds[] = $assignment->getUser()->getId();
            }

            // Remove assignments for users that are no longer assigned
            foreach ($currentAssignments as $assignment) {
                $userId = $assignment->getUser()->getId();
                if (!in_array($userId, $userIds)) {
                    $ticket->removeAssignedTo($assignment->getUser());
                }
            }

            // Add new assignments only for users that don't have existing assignments
            foreach ($validUsers as $user) {
                if (!in_array($user->getId(), $currentUserIds)) {
                    $ticket->addAssignedTo($user);
                }
            }
            
            // Update status if it's pending
            if ($ticket->getStatus() === 'pending') {
                $ticket->setStatus('in_progress');
            }
            
            $em->persist($ticket);
            $em->flush();
            
            $this->addFlash('success', 'Usuarios asignados correctamente al ticket.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error al asignar usuarios al ticket: ' . $e->getMessage());
        }

        return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
    }

    #[Route('/tickets/{id}', name: 'ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket, UserRepository $userRepository): Response
    {
        // Check if user has permission to view this ticket
        $this->denyAccessUnlessGranted('view', $ticket);
        // Get all users and filter by role in PHP
        $allUsers = $userRepository->findAll();
        
        // Get notes ordered by creation date (newest first)
        $notes = $ticket->getNotes()->toArray();
        usort($notes, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        // Filter users by role
        $filteredUsers = array_filter($allUsers, function($user) {
            $roles = $user->getRoles();
            return in_array('ROLE_USER', $roles) || in_array('ROLE_AUDITOR', $roles);
        });
        
        // Sort users by name
        usort($filteredUsers, function($a, $b) {
            return strcmp($a->getNombre(), $b->getNombre());
        });
        
        // Check if current user is assigned to this ticket
        $isAssigned = false;
        if ($this->getUser()) {
            foreach ($ticket->getTicketAssignments() as $assignment) {
                if ($assignment->getUser() === $this->getUser()) {
                    $isAssigned = true;
                    break;
                }
            }
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'users' => $filteredUsers,
            'isAssigned' => $isAssigned,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'isAuditor' => $this->isGranted('ROLE_AUDITOR'),
            'isCreator' => $ticket->getCreatedBy() === $this->getUser(),
            'notes' => $notes,
        ]);
    }

    #[Route('/tickets/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Ticket actualizado correctamente.');
            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/{id}', name: 'ticket_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($ticket);
            $this->entityManager->flush();
            $this->addFlash('success', 'Ticket eliminado correctamente.');
        }

        return $this->redirectToRoute('ticket_index');
    }
}
