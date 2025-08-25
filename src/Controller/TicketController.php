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
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;

    /**
     * @Route("/tickets/{id}/update-observation", name="ticket_update_observation", methods={"POST"})
     * @IsGranted("ROLE_AUDITOR")
     */
    public function updateObservation(Request $request, Ticket $ticket): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUDITOR');
        
        if ($ticket->getStatus() !== self::STATUS_COMPLETED && $ticket->getStatus() !== self::STATUS_REJECTED) {
            $this->addFlash('error', 'Solo se pueden editar observaciones en tickets completados o rechazados.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        $observation = $request->request->get('observation');
        
        if (empty(trim($observation))) {
            $this->addFlash('error', 'La observación no puede estar vacía.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        $ticket->setObservation($observation);
        $ticket->setUpdatedAt(new \DateTimeImmutable());
        
        $this->em->persist($ticket);
        $this->em->flush();
        
        $this->addFlash('success', 'La observación ha sido actualizada correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TicketRepository $ticketRepository,
        UserRepository $userRepository
    ) {
        $this->logger = $logger;
        $this->em = $entityManager; // Using $em as the property name for consistency
        $this->ticketRepository = $ticketRepository;
        $this->userRepository = $userRepository;
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
            
            $this->em->persist($ticket);
            $this->em->flush();
            
            $this->addFlash('warning', 'Se ha enviado la propuesta de cambio de estado a los auditores para su revisión.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // If user is auditor or admin, apply the status change directly
        return $this->updateStatus($ticket, $newStatus, $noteContent);
    }
    
    #[Route('/tickets/{id}/approve-proposal', name: 'ticket_approve_proposal', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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
        
        $this->em->persist($note);
        
        // Update the status
        $ticket->setStatus($ticket->getProposedStatus());
        $ticket->setProposedStatus(null);
        $ticket->setProposalNote(null);
        $ticket->setProposedBy(null);
        
        $this->em->persist($ticket);
        $this->em->flush();
        
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
        
        $this->em->persist($note);
        $this->em->flush();
        
        $this->addFlash('success', sprintf('Su sugerencia de %s ha sido guardada en las notas del ticket', strtolower($statusLabel)));
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/tickets/{id}/auditor-action', name: 'ticket_auditor_action', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function auditorAction(Request $request, Ticket $ticket): Response
    {
        $action = $request->request->get('action');
        $description = $request->request->get('description', $request->request->get('observation', ''));
        $assignToId = $request->request->get('assign_to');
        
        // Handle observation update
        if ($action === 'update_observation') {
            if (empty(trim($description))) {
                $this->addFlash('error', 'La observación no puede estar vacía');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
            
            $ticket->setObservation($description);
            $ticket->setUpdatedAt(new \DateTimeImmutable());
            
            $this->em->persist($ticket);
            $this->em->flush();
            
            $this->addFlash('success', 'La observación ha sido actualizada correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // For other actions, require a description
        if (empty(trim($description))) {
            $this->addFlash('error', 'Debe proporcionar una descripción para esta acción');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // Initialize note variable
        $note = null;
        
        // Handle description/note
        if (!empty($description)) {
            $note = new Note();
            $note->setContent($description);
            $note->setCreatedBy($this->getUser());
            $ticket->addNote($note);
        }
        
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
                $this->em->persist($assignment);
                
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
            
            // For finalizing, set the observation and completedBy
            if ($action === 'finalizar') {
                $ticket->setObservation($description);
                $ticket->setCompletedBy($this->getUser());
                $ticket->setUpdatedAt(new \DateTimeImmutable());
            } else {
                // For reject, keep the old behavior
                $ticket->setDescription(
                    $ticket->getDescription() . 
                    "\n\n---\n" .
                    "[{$currentDate}] {$actionLabel} por " . $this->getUser()->getNombre() . 
                    "\n" . 
                    $description
                );
            }
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
            if ($note) {
                $note->setContent($note->getContent() . "\n\nEstado actualizado a: " . $this->getStatusLabel($ticket->getStatus()));
            } else {
                $note = new Note();
                $note->setContent("Estado actualizado a: " . $this->getStatusLabel($ticket->getStatus()));
                $note->setCreatedBy($this->getUser());
                $ticket->addNote($note);
            }
        }
        
        $this->em->persist($ticket);
        if ($note) {
            $this->em->persist($note);
        }
        $this->em->flush();
        
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
            
            $this->em->persist($note);
            $ticket->addNote($note);
        }

        // Set the status
        $ticket->setStatus($status);
        
        // If completing the ticket, set the completedAt timestamp
        if ($status === self::STATUS_COMPLETED) {
            $ticket->setCompletedAt(new \DateTimeImmutable());
        }
        
        $this->em->persist($ticket);
        $this->em->flush();

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
        $this->em->persist($ticket);
        $this->em->flush();
        
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
        
        $this->em->persist($ticket);
        $this->em->flush();
        
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets', name: 'ticket_index')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20; // Tickets por página
        $offset = ($page - 1) * $limit;
        
        // Filtros
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'DESC');
        $area = $request->query->get('area');
        
        // Construir query base
        $qb = $this->em->createQueryBuilder();
        $qb->select('t')
           ->from(Ticket::class, 't')
           ->leftJoin('t.createdBy', 'u')
           ->leftJoin('t.ticketAssignments', 'ta')
           ->leftJoin('ta.user', 'assignedUser');
        
        // Aplicar filtros
        if ($status && $status !== 'all') {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':search'),
                    $qb->expr()->like('t.description', ':search'),
                    $qb->expr()->like('t.idSistemaInterno', ':search'),
                    $qb->expr()->like('u.nombre', ':search'),
                    $qb->expr()->like('u.apellido', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }
        
        if ($area && $area !== 'all') {
            $qb->andWhere('t.areaOrigen = :area')
               ->setParameter('area', $area);
        }
        
        // Aplicar ordenamiento
        $validSortFields = ['createdAt', 'title', 'status', 'areaOrigen'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'createdAt';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $qb->orderBy('t.' . $sortBy, $sortOrder);
        
        // Si no es admin, filtrar por usuario creador o asignado
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('t.createdBy', ':currentUser'),
                    $qb->expr()->eq('assignedUser', ':currentUser')
                )
            )
            ->setParameter('currentUser', $this->getUser());
        }
        
        // Contar total de tickets
        $countQb = clone $qb;
        $totalTickets = $countQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();
        
        // Aplicar paginación
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);
        
        $tickets = $qb->getQuery()->getResult();
        
        // Obtener usuarios para asignación
        $users = $this->em->getRepository(User::class)->findAll();
        
        // Obtener áreas únicas para filtros
        $areas = $this->em->createQueryBuilder()
            ->select('DISTINCT t.areaOrigen')
            ->from(Ticket::class, 't')
            ->where('t.areaOrigen IS NOT NULL')
            ->andWhere('t.areaOrigen != :empty')
            ->setParameter('empty', '')
            ->orderBy('t.areaOrigen', 'ASC')
            ->getQuery()
            ->getResult();
        
        $areas = array_column($areas, 'areaOrigen');
        
        // Calcular estadísticas
        $stats = [
            'total' => $totalTickets,
            'pending' => $this->em->getRepository(Ticket::class)->count(['status' => 'pending']),
            'in_progress' => $this->em->getRepository(Ticket::class)->count(['status' => 'in_progress']),
            'completed' => $this->em->getRepository(Ticket::class)->count(['status' => 'completed']),
            'rejected' => $this->em->getRepository(Ticket::class)->count(['status' => 'rejected']),
            'delayed' => $this->em->getRepository(Ticket::class)->count(['status' => 'delayed']),
        ];
        
        // Calcular páginas
        $totalPages = ceil($totalTickets / $limit);
        
        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'users' => $users,
            'areas' => $areas,
            'stats' => $stats,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'limit' => $limit,
                'total_items' => $totalTickets,
            ],
            'filters' => [
                'status' => $status,
                'search' => $search,
                'area' => $area,
                'sort' => $sortBy,
                'order' => $sortOrder,
            ],
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
            $this->em->persist($ticket);
            $this->em->flush();
            
            $this->addFlash('success', 'Ticket creado exitosamente.');
            return $this->redirectToRoute('ticket_index');
        }
        
        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tickets/assign', name: 'ticket_assign', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function assign(Request $request): Response
    {
        $this->logger->info('=== TICKET ASSIGNMENT START ===');
        
        try {
            // Log request details
            $this->logger->info('Request method: ' . $request->getMethod());
            $this->logger->info('Content-Type: ' . $request->headers->get('Content-Type'));
            $this->logger->info('Request data: ' . print_r($request->request->all(), true));
            
            // Verify CSRF token
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('assign_ticket', $token)) {
                $this->logger->error('Invalid CSRF token');
                throw $this->createAccessDeniedException('Token de seguridad inválido');
            }
            
            // Get ticket ID and validate
            $ticketId = $request->request->get('ticket_id');
            if (!$ticketId) {
                $this->logger->error('No ticket_id provided in request');
                throw new \InvalidArgumentException('ID de ticket no proporcionado');
            }
            
            // Get the ticket
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                $this->logger->error('Ticket not found with ID: ' . $ticketId);
                throw $this->createNotFoundException('Ticket no encontrado');
            }
            
            // Get user IDs
            $userIds = $request->request->all('assigned_users');
            if (empty($userIds)) {
                $this->logger->warning('No user IDs provided in request');
                $this->addFlash('warning', 'Debe seleccionar al menos un usuario');
                return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
            }
            
            $this->logger->info('Processing assignment for ticket: ' . $ticketId);
            $this->logger->info('Selected users: ' . print_r($userIds, true));
            
            // Process user assignments
            $assignedUsers = [];
            foreach ($userIds as $userId) {
                $user = $this->userRepository->find($userId);
                if ($user) {
                    $assignedUsers[] = $user;
                    
                    // Create new assignment if it doesn't exist
                    $assignment = new TicketAssignment();
                    $assignment->setTicket($ticket);
                    $assignment->setUser($user);
                    $assignment->setAssignedAt(new \DateTimeImmutable());
                    
                    $this->em->persist($assignment);
                }
            }
            
            // Update ticket status if needed
            if (count($assignedUsers) > 0 && $ticket->getStatus() === self::STATUS_PENDING) {
                $ticket->setStatus(self::STATUS_IN_PROGRESS);
                $this->em->persist($ticket);
            }
            
            $this->em->flush();
            
            $this->logger->info('Successfully assigned users to ticket: ' . $ticketId);
            $this->addFlash('success', 'Usuarios asignados correctamente al ticket.');
            
        } catch (\Exception $e) {
            $this->logger->error('Error assigning users to ticket: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('danger', 'Error al asignar usuarios al ticket: ' . $e->getMessage());
        }
        
        // Redirect back to the ticket
        return $this->redirectToRoute('ticket_show', [
            'id' => $ticketId ?? 0
        ]);
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
            'notes' => $notes,
            'isAssigned' => $isAssigned,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'isAuditor' => $this->isGranted('ROLE_AUDITOR'),
            'isCreator' => $ticket->getCreatedBy() === $this->getUser(),
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
            $assignedUsers = $form->get('ticketAssignments')->getData();
            
            // Clear existing assignments
            foreach ($ticket->getTicketAssignments() as $assignment) {
                $ticket->removeTicketAssignment($assignment);
                $this->em->remove($assignment);
            }
            
            // Add new assignments
            $assignedUserIds = $request->request->all('assignedUsers');
            if (!empty($assignedUserIds)) {
                foreach ($assignedUserIds as $userId) {
                    $user = $this->userRepository->find($userId);
                    if ($user) {
                        $assignment = new TicketAssignment();
                        $assignment->setUser($user);
                        $assignment->setTicket($ticket);
                        $assignment->setAssignedAt(new \DateTime());
                        $this->em->persist($assignment);
                        $ticket->addTicketAssignment($assignment);
                    }
                }
            }
            
            $this->em->persist($ticket);
            $this->em->flush();
            
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

        $this->em->persist($note);
        $this->em->flush();

        $this->addFlash('success', 'La propuesta ha sido rechazada correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets/{id}', name: 'ticket_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $this->em->remove($ticket);
            $this->em->flush();
            $this->addFlash('success', 'Ticket eliminado correctamente.');
        }

        return $this->redirectToRoute('ticket_index');
    }
}
