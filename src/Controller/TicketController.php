<?php

namespace App\Controller;

use App\Entity\Note;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Ticket;
use App\Entity\TicketAssignment;
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
    
    private function getStatusLabel(string $status): string
    {
        return [
            self::STATUS_PENDING => 'pendiente',
            self::STATUS_IN_PROGRESS => 'en progreso',
            self::STATUS_COMPLETED => 'completado',
            self::STATUS_REJECTED => 'rechazado'
        ][$status] ?? 'desconocido';
    }
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository
    ) {
    }
    
    #[Route('/tickets/{id}/propose-status/{status}', name: 'ticket_propose_status', methods: ['POST'])]
    #[IsGranted('propose_status', 'ticket')]
    public function proposeStatus(Request $request, Ticket $ticket, string $status): Response
    {
        $validStatuses = [
            'rechazado' => self::STATUS_REJECTED,
            'en_progreso' => self::STATUS_IN_PROGRESS,
            'completado' => self::STATUS_COMPLETED,
            'status' => null // For the dropdown in index page
        ];
        
        // Handle dropdown form submission from index page
        if ($status === 'status') {
            $status = $request->request->get('status');
            if (!array_key_exists($status, $validStatuses)) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_index');
            }
            $newStatus = $validStatuses[$status];
            $noteContent = 'Cambio de estado a ' . $status;
        } else {
            if (!array_key_exists($status, $validStatuses)) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
            $newStatus = $validStatuses[$status];
            $noteContent = $request->request->get('note', '');
            
            if (empty(trim($noteContent))) {
                $this->addFlash('error', 'Debe proporcionar una razón para el cambio de estado');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
        }
        
        // If user is not an auditor or admin, create a proposal
        if (!$this->isGranted('ROLE_AUDITOR') && !$this->isGranted('ROLE_ADMIN')) {
            $ticket->setProposedStatus($newStatus);
            $ticket->setProposalNote($noteContent);
            $ticket->setProposedBy($this->getUser());
            
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();
            
            $this->addFlash('warning', 'Se ha enviado la propuesta de cambio de estado a los auditores para su revisión.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // If user is auditor or admin, apply the status change directly
        return $this->updateStatus($ticket, $newStatus, $noteContent);
    }
    
    #[Route('/tickets/{id}/approve-proposal', name: 'ticket_approve_proposal', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function approveProposal(Request $request, Ticket $ticket): Response
    {
        if (!$ticket->getProposedStatus()) {
            $this->addFlash('error', 'No hay una propuesta de cambio de estado pendiente para este ticket');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        $note = new Note();
        $note->setContent(sprintf(
            "Propuesta aprobada por %s\n\n%s",
            $this->getUser()->getUserIdentifier(),
            $ticket->getProposalNote()
        ));
        $note->setTicket($ticket);
        $note->setCreatedBy($this->getUser());
        
        $this->entityManager->persist($note);
        
        // Update the status
        $ticket->setStatus($ticket->getProposedStatus());
        $ticket->setProposedStatus(null);
        $ticket->setProposalNote(null);
        $ticket->setProposedBy(null);
        
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La propuesta ha sido aprobada y el estado del ticket ha sido actualizado.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/tickets/{id}/suggest-rejection', name: 'ticket_suggest_rejection', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function suggestRejection(Request $request, Ticket $ticket): Response
    {
        return $this->suggestStatus($request, $ticket, self::STATUS_REJECTED, 'rechazo');
    }
    
    #[Route('/tickets/{id}/suggest-status/{statusType}', name: 'ticket_suggest_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function suggestStatus(Request $request, Ticket $ticket, string $statusType): Response
    {
        $suggestion = $request->request->get('suggestion', '');
        
        if (empty(trim($suggestion))) {
            $this->addFlash('error', 'Debe proporcionar una explicación para esta sugerencia');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        $statusMap = [
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'rechazado' => 'Rechazado'
        ];
        
        if (!array_key_exists($statusType, $statusMap)) {
            throw $this->createNotFoundException('Tipo de estado no válido');
        }
        
        $statusLabel = $statusMap[$statusType];
        
        $note = new Note();
        $note->setContent(sprintf(
            "Sugerencia de %s\nUsuario: %s %s\nFecha: %s\n\n%s",
            $statusLabel,
            $this->getUser()->getNombre(),
            $this->getUser()->getApellido(),
            (new \DateTime())->format('d/m/Y H:i'),
            $suggestion
        ));
        $note->setTicket($ticket);
        $note->setCreatedBy($this->getUser());
        
        $this->entityManager->persist($note);
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf('Su sugerencia de %s ha sido guardada en las notas del ticket', strtolower($statusLabel)));
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/tickets/{id}/auditor-action', name: 'ticket_auditor_action', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function auditorAction(Request $request, Ticket $ticket): Response
    {
        $action = $request->request->get('action');
        $description = $request->request->get('description', '');
        $assignToId = $request->request->get('assign_to');
        
        if (empty(trim($description))) {
            $this->addFlash('error', 'Debe proporcionar una descripción para esta acción');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // Create a note for the action
        $note = new Note();
        $note->setContent($description);
        $note->setCreatedBy($this->getUser());
        $ticket->addNote($note);
        
        // Handle user assignment if provided
        if ($assignToId && $assignToId !== '') {
            $user = $this->userRepository->find($assignToId);
            if ($user) {
                $previousUser = $ticket->getTakenBy();
                $ticket->setTakenBy($user);
                
                // Create a new assignment record
                $assignment = new TicketAssignment();
                $assignment->setTicket($ticket);
                $assignment->setUser($user);
                $assignment->setAssignedBy($this->getUser());
                $assignment->setAssignedAt(new \DateTimeImmutable());
                $this->entityManager->persist($assignment);
                
                // Update note content with assignment details
                $assignmentNote = "\n\nTicket reasignado a: " . $user->getFullName();
                if ($previousUser) {
                    $assignmentNote = "\n\nTicket reasignado de " . $previousUser->getFullName() . " a " . $user->getFullName();
                }
                $note->setContent($note->getContent() . $assignmentNote);
            }
        }
        
        // Update ticket description for reject/finalize actions
        if (in_array($action, ['rechazar', 'finalizar'])) {
            $currentDate = (new \DateTime())->format('d/m/Y H:i');
            $actionLabel = $action === 'rechazar' ? 'Rechazado' : 'Finalizado';
            $ticket->setDescription(
                $ticket->getDescription() . 
                "\n\n---\n" .
                "[{$currentDate}] {$actionLabel} por " . $this->getUser()->getNombre() . 
                "\n" . 
                $description
            );
        }
        
        // Update status based on action
        $statusUpdated = false;
        $statusMessage = '';
        
        switch ($action) {
            case 'rechazar':
                $ticket->setStatus(self::STATUS_REJECTED);
                $statusUpdated = true;
                $statusMessage = 'El ticket ha sido rechazado';
                break;
            case 'finalizar':
                $ticket->setStatus(self::STATUS_COMPLETED);
                $statusUpdated = true;
                $statusMessage = 'El ticket ha sido finalizado';
                break;
            case 'continuar':
            case 'reasignar':
                $ticket->setStatus(self::STATUS_IN_PROGRESS);
                $statusUpdated = true;
                $statusMessage = $action === 'reasignar' 
                    ? 'El ticket ha sido reasignado' 
                    : 'El ticket ha sido marcado como en progreso';
                break;
        }
        
        if ($statusUpdated) {
            $note->setContent($note->getContent() . "\n\nEstado actualizado a: " . $this->getStatusLabel($ticket->getStatus()));
        }
        
        $this->entityManager->persist($ticket);
        $this->entityManager->persist($note);
        $this->entityManager->flush();
        
        $this->addFlash('success', $statusMessage ?: 'La acción ha sido registrada');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    private function updateStatus(Ticket $ticket, string $status, ?string $noteContent = null): Response
    {
        // Create a note if content is provided
        if (!empty(trim($noteContent))) {
            $note = new Note();
            $note->setContent(sprintf(
                "Acción: %s\n\n%s",
                $this->getStatusLabel($status),
                $noteContent
            ));
            $note->setTicket($ticket);
            $note->setCreatedBy($this->getUser());
            
            $this->entityManager->persist($note);
            $ticket->addNote($note);
        }

        // Set the status
        $ticket->setStatus($status);
        
        // If completing the ticket, set the completedAt timestamp
        if ($status === self::STATUS_COMPLETED) {
            $ticket->setCompletedAt(new \DateTimeImmutable());
        }
        
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $statusMessage = $this->getStatusLabel($status);
        $this->addFlash('success', sprintf('El ticket ha sido marcado como %s', $statusMessage));
        
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
        // Create form with the ticket entity directly
        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the assigned users from the form
            $assignedUsers = $form->get('assignedUsers')->getData();
            
            // Clear existing assignments
            foreach ($ticket->getTicketAssignments() as $assignment) {
                $ticket->removeTicketAssignment($assignment);
                $this->entityManager->remove($assignment);
            }
            
            // Add new assignments
            if ($assignedUsers) {
                foreach ($assignedUsers as $user) {
                    $assignment = new TicketAssignment();
                    $assignment->setUser($user);
                    $assignment->setTicket($ticket);
                    $assignment->setAssignedAt(new \DateTime());
                    $this->entityManager->persist($assignment);
                    $ticket->addTicketAssignment($assignment);
                }
            }
            
            $this->entityManager->persist($ticket);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Ticket actualizado correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/{id}/reject-proposal', name: 'ticket_reject_proposal', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rejectProposal(Request $request, Ticket $ticket): Response
    {
        if (!$ticket->getProposedStatus()) {
            $this->addFlash('error', 'No hay ninguna propuesta para rechazar.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        $reason = $request->request->get('reason', '');
        
        // Create a note about the rejection
        $note = new Note();
        $note->setContent(sprintf(
            'Propuesta de cambio a "%s" rechazada. Razón: %s',
            $this->getStatusLabel($ticket->getProposedStatus()),
            $reason ?: 'No se proporcionó una razón.'
        ));
        $note->setUser($this->getUser());
        $ticket->addNote($note);

        // Clear the proposal
        $ticket->setProposedStatus(null);
        $ticket->setProposedBy(null);
        $ticket->setProposedAt(null);
        $ticket->setProposalNote(null);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->addFlash('success', 'La propuesta ha sido rechazada correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
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
