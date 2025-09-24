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
use App\Repository\TicketAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class TicketController extends AbstractController
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_DELAYED = 'delayed';
    
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
        private readonly TicketRepository $ticketRepository,
        private readonly TicketAssignmentRepository $ticketAssignmentRepository,
        private readonly UserRepository $userRepository
    ) {}
    
    #[Route('/tickets', name: 'ticket_index')]
    public function index(Request $request, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('per_page', 10);
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        $area = $request->query->get('area');
        $sort = $request->query->get('sort', 'createdAt');
        $direction = $request->query->get('direction', 'DESC');
        
        // Validate and sanitize inputs
        $limit = max(5, min(100, $limit));
        $validSorts = ['id', 'title', 'status', 'area_origen', 'createdAt'];
        $sort = in_array($sort, $validSorts) ? $sort : 'createdAt';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        // Initialize the count query builder
        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(t.id) as count')
            ->from(Ticket::class, 't')
            ->leftJoin('t.ticketAssignments', 'ta')
            ->leftJoin('ta.user', 'assignedUser');
            
        // Apply the same filters to the count query
        if ($status) {
            $countQb->andWhere('t.status = :status')
                   ->setParameter('status', $status);
        }
        
        // Get the total count after applying status filter
        $totalTickets = (int) $countQb->getQuery()->getSingleScalarResult();
        
        if ($search) {
            $countQb->andWhere(
                $countQb->expr()->orX(
                    $countQb->expr()->like('t.title', ':search'),
                    $countQb->expr()->like('t.description', ':search'),
                    $countQb->expr()->like('t.idSistemaInterno', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }
        
        if ($area) {
            $countQb->andWhere('t.area_origen = :area')
                   ->setParameter('area', $area);
        }
        
        // Apply access control to count query
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            $countQb->andWhere(
                $countQb->expr()->orX(
                    $countQb->expr()->eq('t.createdBy', ':currentUser'),
                    $countQb->expr()->eq('assignedUser', ':currentUser')
                )
            )->setParameter('currentUser', $this->getUser());
        }
        
        // Use the pre-calculated total
        
        // Build the main query for pagination - only non-archived tickets
        $qb = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->leftJoin('t.createdBy', 'createdBy')
            ->leftJoin('t.ticketAssignments', 'ta')
            ->leftJoin('ta.user', 'assignedUser')
            ->where('t.status != :archived')
            ->setParameter('archived', 'archived')
            ->groupBy('t.id');
            
        // Apply filters
        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':search'),
                    $qb->expr()->like('t.description', ':search'),
                    $qb->expr()->like('t.idSistemaInterno', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }
        
        if ($area) {
            $qb->andWhere('t.area_origen = :area')
               ->setParameter('area', $area);
        }
        
        // Apply sorting
        $validSorts = ['id', 'title', 'status', 'area_origen', 'createdAt'];
        $sort = in_array($sort, $validSorts) ? $sort : 'createdAt';
        $direction = in_array(strtoupper($direction), ['ASC', 'DESC']) ? $direction : 'DESC';
        
        // Special handling for relations
        if ($sort === 'createdBy') {
            $qb->addSelect('createdBy')
               ->orderBy('createdBy.apellido', $direction);
        } else {
            $qb->orderBy('t.' . $sort, $direction);
        }
        
        // Apply access control
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('t.createdBy', ':currentUser'),
                    $qb->expr()->eq('assignedUser', ':currentUser')
                )
            )->setParameter('currentUser', $this->getUser());
        }
        
        // Create base query builder
        $qb = $this->ticketRepository->createQueryBuilder('t')
            ->leftJoin('t.createdBy', 'createdBy')
            ->leftJoin('t.ticketAssignments', 'ta')
            ->leftJoin('ta.user', 'assignedUser')
            ->where('t.status != :archived')
            ->setParameter('archived', 'archived');
            
        // Apply filters
        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.title', ':search'),
                    $qb->expr()->like('t.description', ':search'),
                    $qb->expr()->like('t.idSistemaInterno', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }
        
        if ($area) {
            $qb->andWhere('t.area_origen = :area')
               ->setParameter('area', $area);
        }
        
        // Apply access control
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('t.createdBy', ':currentUser'),
                    $qb->expr()->eq('assignedUser', ':currentUser')
                )
            )->setParameter('currentUser', $this->getUser());
        }
        
        // Get total count
        $totalTickets = (clone $qb)
            ->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Apply sorting
        if ($sort === 'createdBy') {
            $qb->orderBy('createdBy.apellido', $direction);
        } else {
            $qb->orderBy('t.' . $sort, $direction);
        }
        
        // Apply pagination
        $offset = ($page - 1) * $limit;
        $tickets = $qb->select('t', 'createdBy', 'ta', 'assignedUser')
            ->distinct()
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        // Prepare pagination data for the template
        $pagination = [
            'items' => $tickets,
            'total' => (int)$totalTickets,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => max(1, ceil($totalTickets / $limit)),
            'from' => $totalTickets > 0 ? $offset + 1 : 0,
            'to' => $totalTickets > 0 ? min($offset + $limit, $totalTickets) : 0,
            'has_more' => ($page * $limit) < $totalTickets
        ];
        
        // Get unique areas for filter
        $areas = $this->em->createQueryBuilder()
            ->select('DISTINCT t.area_origen')
            ->from(Ticket::class, 't')
            ->where('t.area_origen IS NOT NULL')
            ->andWhere('t.area_origen != :empty')
            ->setParameter('empty', '')
            ->orderBy('t.area_origen', 'ASC')
            ->getQuery()
            ->getResult();
            
        $users = $this->userRepository->findBy(['isActive' => true], ['apellido' => 'ASC', 'nombre' => 'ASC']);
        
        return $this->render('ticket/index.html.twig', [
            'tickets' => $pagination['items'],
            'pagination' => $pagination,
            'areas' => $areas,
            'sort' => $sort,
            'direction' => $direction,
            'users' => $users, // Add users to the template context
            'current_filters' => [
                'status' => $status,
                'search' => $search,
                'area' => $area,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'total_tickets' => $pagination['total'], // Use the total from pagination array
        ]);
    }

    /**
     * Get list of users for assignment
     */
    #[Route('/api/users/list', name: 'user_list', methods: ['GET'])]
    public function getUsersList(UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $users = $userRepository->findAllActiveUsers();

        $userData = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
                'username' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ];
        }, $users);

        return $this->json([
            'success' => true,
            'users' => $userData
        ]);
    }

    #[Route('/tickets/distribution', name: 'ticket_distribution')]
    #[Route('/tickets/distribution/chart', name: 'ticket_distribution_chart')]
    public function ticketDistributionChart(TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('ticket/distribution.html.twig');
    }

    #[Route('/tickets/distribution/data', name: 'ticket_distribution_data')]
    public function ticketDistributionData(TicketRepository $ticketRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $statusCounts = $ticketRepository->countTicketsByStatus();
        $areaCounts = $ticketRepository->countTicketsByArea();

        return $this->json([
            'status' => [
                'labels' => array_map([$this, 'getStatusLabel'], array_keys($statusCounts)),
                'data' => array_values($statusCounts),
                'backgroundColor' => [
                    '#ffc107', // Amarillo para pendientes
                    '#17a2b8', // Azul claro para en progreso
                    '#28a745', // Verde para completados
                    '#dc3545'  // Rojo para rechazados
                ]
            ],
            'area' => [
                'labels' => array_keys($areaCounts),
                'data' => array_values($areaCounts),
                'backgroundColor' => [
                    '#007bff',
                    '#6f42c1',
                    '#e83e8c',
                    '#20c997',
                    '#fd7e14',
                    '#6c757d'
                ]
            ]
        ]);
    }

    private function getStatusLabel(string $status): string
    {
        return [
            self::STATUS_PENDING => 'pendiente',
            self::STATUS_IN_PROGRESS => 'en progreso',
            self::STATUS_COMPLETED => 'completado',
            self::STATUS_REJECTED => 'rechazado'
        ][$status] ?? 'desconocido';
    }

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


    #[Route('/tickets/{id}/propose-status/{status}', name: 'ticket_propose_status', methods: ['POST'])]
    #[IsGranted('propose_status', 'ticket')]
    public function proposeStatus(Request $request, Ticket $ticket, string $status): Response
    {
        $statusMap = [
            'pending' => self::STATUS_PENDING,
            'in_progress' => self::STATUS_IN_PROGRESS,
            'completed' => self::STATUS_COMPLETED,
            'rejected' => self::STATUS_REJECTED,
            'status' => null // For the dropdown in index page
        ];

        // Handle dropdown form submission from index page
        if ($status === 'status') {
            $status = $request->request->get('status');
            if (!array_key_exists($status, $statusMap)) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_index');
            }
            $newStatus = $statusMap[$status];
            $statusLabels = [
                'pending' => 'Pendiente',
                'in_progress' => 'En progreso',
                'completed' => 'Completado',
                'rejected' => 'Rechazado'
            ];
            $noteContent = 'Cambio de estado a ' . ($statusLabels[$status] ?? $status);
        } else {
            if (!array_key_exists($status, $statusMap)) {
                $this->addFlash('error', 'Estado no válido');
                return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
            }
            $newStatus = $statusMap[$status];
            $noteContent = $request->request->get('note', '');

            if (empty(trim($noteContent))) {
                $statusLabels = [
                    'pending' => 'Pendiente',
                    'in_progress' => 'En progreso',
                    'completed' => 'Completado',
                    'rejected' => 'Rechazado'
                ];
                $noteContent = 'Cambio de estado a ' . ($statusLabels[$status] ?? $status);
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

        // If completing the ticket, set the completedBy user and current time
        if ($status === self::STATUS_COMPLETED) {
            $ticket->setCompletedBy($this->getUser());
            $ticket->setUpdatedAt(new \DateTimeImmutable());
        }

        $this->em->persist($ticket);
        $this->em->flush();

        $statusMessage = $this->getStatusLabel($status);
        $this->addFlash('success', sprintf('El ticket ha sido marcado como %s', $statusMessage));

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'ticket')]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_new_ticket' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Ticket actualizado correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
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


    #[Route('/tickets/new', name: 'ticket_new')]
    public function new(Request $request, UserRepository $userRepository): Response
    {
        // Check if user has AUDITOR role
        if (!$this->isGranted('ROLE_AUDITOR')) {
            $this->addFlash('error', 'No tienes permiso para crear tickets. Solo los usuarios con rol AUDITOR pueden crear nuevos tickets.');
            return $this->redirectToRoute('ticket_index');
        }

        $ticket = new Ticket();
        $ticket->setCreatedBy($this->getUser());
        
        // Handle external ID from request before creating the form
        $ticketData = $request->request->all('ticket');
        $externalId = isset($ticketData['idSistemaInterno']) ? trim($ticketData['idSistemaInterno']) : '';
        
        // If external ID is provided, check for duplicates before form validation
        if (!empty($externalId)) {
            $existingTicket = $this->ticketRepository->findOneBy(['idSistemaInterno' => $externalId]);
            if ($existingTicket) {
                $this->addFlash('error', 'El ID Externo ingresado ya está en uso. Por favor, ingrese un ID único.');
                // Recreate the form with submitted data
                $form = $this->createForm(TicketType::class, $ticket, [
                    'is_admin' => $this->isGranted('ROLE_ADMIN'),
                    'is_new_ticket' => true
                ]);
                $form->handleRequest($request);
                
                return $this->render('ticket/new.html.twig', [
                    'ticket' => $ticket,
                    'form' => $form->createView(),
                    'users' => $userRepository->findAll(),
                    'assigned_users' => []
                ]);
            }
            
            // If we get here, the external ID is valid and unique
            $ticket->setIdSistemaInterno($externalId);
            $this->logger->info('Using provided external ID: ' . $externalId);
        }

        $form = $this->createForm(TicketType::class, $ticket, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_new_ticket' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if there are any assigned users
            $assignedUsers = $form->get('assignedUsers')->getData();
            
            // If there are assigned users, update status to IN_PROGRESS
            if (count($assignedUsers) > 0) {
                $ticket->setStatus(Ticket::STATUS_IN_PROGRESS);
                
                // Add assigned users to the ticket
                foreach ($assignedUsers as $user) {
                    $ticket->addAssignedTo($user);
                }
            } else {
                $ticket->setStatus(Ticket::STATUS_PENDING);
            }
            
            try {
                // If no external ID was provided, generate an internal one
                if (empty($externalId)) {
                    // Start a transaction for ID generation
                    $this->em->beginTransaction();
                    
                    // Get the maximum internal ticket number with INT- prefix
                    $conn = $this->em->getConnection();
                    $sql = "SELECT id_sistema_interno FROM ticket WHERE id_sistema_interno LIKE 'INT-%' ORDER BY id DESC LIMIT 1";
                    $stmt = $conn->prepare($sql);
                    $result = $stmt->executeQuery();
                    $lastId = $result->fetchOne();
                    
                    // Extract the highest number
                    $nextNumber = 1;
                    if ($lastId && preg_match('/^INT-(\d+)$/', $lastId, $matches)) {
                        $nextNumber = (int)$matches[1] + 1;
                    }
                    
                    // Set the new ID in format INT-1, INT-2, etc.
                    $newId = 'INT-' . $nextNumber;
                    $ticket->setIdSistemaInterno($newId);
                    
                    // Debug: Log the generated ID
                    $this->logger->info('Generated new internal ticket ID: ' . $newId);
                    
                    $this->em->commit();
                }
            } catch (\Exception $e) {
                if ($this->em->getConnection()->isTransactionActive()) {
                    $this->em->rollback();
                }
                
                $this->logger->error('Error processing ticket ID: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->addFlash('error', 'Error al procesar el ID del ticket: ' . $e->getMessage());
                
                return $this->render('ticket/new.html.twig', [
                    'ticket' => $ticket,
                    'form' => $form->createView(),
                ]);
            }

            // Log the final state before saving
            $this->logger->info('Final ticket state before save:', [
                'ticketId' => $ticket->getId(),
                'status' => $ticket->getStatus(),
                'assignedUsers' => $ticket->getAssignedUsers()->map(fn($u) => $u->getId())->toArray()
            ]);

            // Final save with all changes
            $this->em->persist($ticket);
            $this->em->flush();

            $this->addFlash('success', '¡Ticket creado exitosamente!');
            return $this->redirectToRoute('ticket_index');
        }

        // Get all users for the assign modal
        $users = $userRepository->findAll();

        // Get any previously assigned users from session
        $assignedUsers = [];
        if ($request->getSession()->has('last_assigned_users')) {
            $assignedUserIds = $request->getSession()->get('last_assigned_users');
            $assignedUsers = $userRepository->findBy(['id' => $assignedUserIds]);
            $request->getSession()->remove('last_assigned_users');
        }

        return $this->render('ticket/new.html.twig', [
            'form' => $form->createView(),
            'users' => $users,
            'assigned_users' => $assignedUsers,
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

            // Get existing assignments to prevent duplicates
            $existingAssignments = [];
            foreach ($ticket->getTicketAssignments() as $assignment) {
                $existingAssignments[$assignment->getUser()->getId()] = true;
            }

            // Process user assignments
            $assignedUsers = [];
            $newAssignments = 0;
            
            foreach ($userIds as $userId) {
                $user = $this->userRepository->find($userId);
                if ($user) {
                    // Skip if user is already assigned
                    if (isset($existingAssignments[$user->getId()])) {
                        $this->logger->info(sprintf('User %s is already assigned to ticket %s', $user->getId(), $ticketId));
                        continue;
                    }
                    
                    $assignedUsers[] = $user;

                    // Create new assignment
                    $assignment = new TicketAssignment();
                    $assignment->setTicket($ticket);
                    $assignment->setUser($user);
                    $assignment->setAssignedAt(new \DateTimeImmutable());

                    $this->em->persist($assignment);
                    $newAssignments++;
                }
            }
            
            if ($newAssignments === 0) {
                $this->logger->warning('No new users to assign to ticket');
                $this->addFlash('warning', 'Los usuarios seleccionados ya están asignados a este ticket.');
                return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
            }

            // Update ticket status based on assignments
            if (count($ticket->getTicketAssignments()) > 0) {
                $ticket->setStatus(self::STATUS_IN_PROGRESS);
            } else {
                $ticket->setStatus(self::STATUS_PENDING);
            }

            $this->em->persist($ticket);
            $this->em->flush();

            $this->logger->info('Successfully assigned users to ticket: ' . $ticketId);
            $this->addFlash('success', 'Usuarios asignados correctamente al ticket.');

        } catch (\Exception $e) {
            $this->logger->error('Error assigning users to ticket: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('danger', 'Error al asignar usuarios al ticket: ' . $e->getMessage());

            // If we have a valid ticket ID, redirect to the ticket, otherwise go to ticket list
            if (isset($ticketId) && $ticket = $this->ticketRepository->find($ticketId)) {
                return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
            }

            return $this->redirectToRoute('ticket_index');
        }

        // If we get here, the operation was successful
        if (isset($ticket) && $ticket instanceof Ticket) {
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        // Fallback to ticket list if we can't determine the ticket
        return $this->redirectToRoute('ticket_index');
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
            if ($a->getCreatedAt() === $b->getCreatedAt()) {
                return 0;
            }
            return ($a->getCreatedAt() < $b->getCreatedAt()) ? 1 : -1;
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

        // Ensure ticket status is in sync with assignments
        if ($ticket->getTicketAssignments()->count() > 0 && $ticket->getStatus() === self::STATUS_PENDING) {
            $ticket->setStatus(self::STATUS_IN_PROGRESS);
            $this->em->persist($ticket);
            $this->em->flush();
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

    #[Route('/tickets/{id}/reject', name: 'ticket_reject', methods: ['POST'])]
    public function reject(Request $request, Ticket $ticket): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }
        $this->denyAccessUnlessGranted('reject', $ticket);

        $reason = $request->request->get('reason', '');
        $noteContent = 'Ticket rechazado por ' . $this->getUser()->getFullName() . ".\n\n";
        $noteContent .= "Motivo del rechazo:\n" . $reason;

        $this->updateStatus($ticket, self::STATUS_REJECTED, $noteContent);

        $this->addFlash('success', 'El ticket ha sido rechazado correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets/{id}/complete', name: 'ticket_complete', methods: ['POST'])]
    public function complete(Request $request, Ticket $ticket): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }
        $this->denyAccessUnlessGranted('complete', $ticket);

        // Optional CSRF validation scoped to a dedicated field to avoid collisions with other forms
        $submittedToken = $request->request->get('_complete_token');
        if ($submittedToken !== null && !$this->isCsrfTokenValid('ticket_complete_' . $ticket->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Token CSRF inválido');
        }

        $notes = $request->request->get('notes', '');

        // Update the ticket's description with the completion notes
        $ticket->setDescription($notes);

        // Create a note to track who completed the ticket
        $noteContent = 'Ticket marcado como completado por ' . $this->getUser()->getFullName();

        $this->updateStatus($ticket, self::STATUS_COMPLETED, $noteContent);

        $this->addFlash('success', 'El ticket ha sido marcado como completado.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/tickets/{id}/complete/confirm', name: 'ticket_complete_confirm', methods: ['GET'])]
    public function completeConfirm(Ticket $ticket): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_AUDITOR')) {
            throw $this->createAccessDeniedException('No tienes permiso para realizar esta acción');
        }

        // Reuse voter to ensure consistent rules
        $this->denyAccessUnlessGranted('complete', $ticket);

        return $this->render('ticket/complete_confirm.html.twig', [
            'ticket' => $ticket,
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
