<?php
// src/Controller/TicketController.php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketCollaborator;
use App\Form\TicketType;
use App\Form\TicketFilterType;
use App\Repository\TicketRepository;
use App\Repository\TicketCollaboratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\TaskType;
use App\Entity\Task;
use App\Repository\TaskRepository;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    private const ITEMS_PER_PAGE = 15;
    
    #[Route('/lista', name: 'ticket_lista')]
    public function index(
        Request $request,
        TicketRepository $ticketRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        
        $filterForm = $this->createForm(\App\Form\TicketFilterType::class);
        $filterForm->handleRequest($request);

        $searchData = [
            'search' => $request->query->get('search'),
            'estado' => $request->query->get('estado'),
            'departamento' => $request->query->get('departamento') ? (int)$request->query->get('departamento') : null,
            'fechaDesde' => $request->query->get('fechaDesde') ? new \DateTime($request->query->get('fechaDesde')) : null,
            'fechaHasta' => $request->query->get('fechaHasta') ? new \DateTime($request->query->get('fechaHasta')) : null,
        ];

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            if ($filterForm->get('limpiar')->isClicked()) {
                return $this->redirectToRoute('ticket_lista');
            }

            $formData = $filterForm->getData();
            $searchData = [
                'search' => $formData['search'] ?? null,
                'estado' => $formData['estado'] ?? null,
                'departamento' => $formData['departamento'] ? (int)$formData['departamento'] : null,
                'fechaDesde' => $formData['fechaDesde'] ?? null,
                'fechaHasta' => $formData['fechaHasta'] ?? null,
            ];
        }

        // Get sort parameters
        $sortBy = $request->query->get('sort_by', 'createdAt');
        $sortOrder = $request->query->get('sort_order', 'DESC');
        
        $result = $ticketRepository->search(
            $searchData['search'],
            $searchData['estado'],
            $searchData['departamento'],
            $searchData['fechaDesde'],
            $searchData['fechaHasta'],
            $page,
            self::ITEMS_PER_PAGE,
            $sortBy,
            $sortOrder
        );
        
        // Store current sort state for the view
        $sortState = [
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ];

        // Update form with current search data
        $filterForm = $this->createForm(\App\Form\TicketFilterType::class, $searchData);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $result['items'],
            'filter_form' => $filterForm->createView(),
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
            'itemsPerPage' => $result['itemsPerPage'],
            'searchParams' => array_filter($searchData, function($value) {
                return $value !== null && $value !== '';
            }),
            'sortState' => $sortState,
        ]);
    }

    #[Route('/nuevo', name: 'ticket_nuevo')]
    public function nuevo(
        Request $request, 
        EntityManagerInterface $em,
        TicketRepository $ticketRepository,
        TicketCollaboratorRepository $collaboratorRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        $ticket = new Ticket();
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingTicket = $ticketRepository->findOneBy(['ticketId' => $ticket->getTicketId()]);
            
            if ($existingTicket) {
                if ($existingTicket->isCollaborator($user)) {
                    $this->addFlash('warning', 'Ya eres colaborador de este ticket.');
                    return $this->redirectToRoute('ticket_show', ['id' => $existingTicket->getId()]);
                }
                
                // Store ticket ID in session to show the collaboration modal
                $request->getSession()->set('pending_ticket_id', $existingTicket->getId());
                return $this->redirectToRoute('ticket_colaborar', ['id' => $existingTicket->getId()]);
            }

            // Add creator as the first collaborator
            $collaborator = new TicketCollaborator();
            $collaborator->setUser($user);
            $collaborator->setTicket($ticket);
            $ticket->addCollaborator($collaborator);
            
            $em->persist($ticket);
            $em->persist($collaborator);
            $em->flush();

            $this->addFlash('success', '✅ Ticket creado correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/nuevo.html.twig', [
            'form' => $form->createView(),
            'existing_ticket' => null,
        ]);
    }

    #[Route('/mis-tickets', name: 'app_ticket')]
    public function misTickets(TicketRepository $repo): Response
    {
        return $this->redirectToRoute('ticket_lista');
    }

    #[Route('/{id}/colaborar', name: 'ticket_colaborar', methods: ['GET', 'POST'])]
    public function colaborar(
        int $id,
        Request $request,
        TicketRepository $ticketRepository,
        TicketCollaboratorRepository $collaboratorRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket no encontrado');
        }

        // Check if user is already a collaborator
        if ($ticket->isCollaborator($user)) {
            $this->addFlash('info', 'Ya eres colaborador de este ticket.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        if ($request->isMethod('POST')) {
            $collaborator = new TicketCollaborator();
            $collaborator->setUser($user);
            $collaborator->setTicket($ticket);
            
            $em->persist($collaborator);
            $em->flush();
            
            $this->addFlash('success', '¡Ahora eres colaborador de este ticket!');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/colaborar.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}', name: 'ticket_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request, 
        Ticket $ticket, 
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository
    ): Response {
        // Create a new task form
        $task = new Task();
        $task->setTicket($ticket);
        
        $taskForm = $this->createForm(TaskType::class, $task);
        $taskForm->handleRequest($request);

        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Tarea agregada correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'task_form' => $taskForm->createView(),
        ]);
    }

    /**
     * @Route("/ticket/task/{id}/toggle", name="task_toggle_complete", methods={"POST"})
     */
    public function toggleTaskComplete(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $task = $taskRepository->find($id);
        
        if (!$task) {
            return $this->json(['success' => false, 'error' => 'Tarea no encontrada'], 404);
        }
        
        // Ensure the task belongs to a ticket the user can access
        $this->denyAccessUnlessGranted('view', $task->getTicket());
        
        $data = json_decode($request->getContent(), true);
        $completed = $data['completed'] ?? false;
        
        $task->setCompleted($completed);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'completed' => $task->isCompleted(),
            'completedAt' => $task->getCompletedAt() ? $task->getCompletedAt()->format('d/m/Y H:i') : null
        ]);
    }
    
    /**
     * @Route("/ticket/task/{id}", name="task_delete", methods={"DELETE"})
     */
    public function deleteTask(
        int $id,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $task = $taskRepository->find($id);
        
        if (!$task) {
            return $this->json(['success' => false, 'error' => 'Tarea no encontrada'], 404);
        }
        
        // Ensure the task belongs to a ticket the user can access
        $this->denyAccessUnlessGranted('edit', $task->getTicket());
        
        $entityManager->remove($task);
        $entityManager->flush();
        
        return $this->json(['success' => true]);
    }
    
    /**
     * @Route("/ticket/{id}/toggle-complete", name="ticket_toggle_complete", methods={"POST"})
     */
    public function toggleComplete(Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $ticket);

        $ticket->setEstado($ticket->getEstado() === 'completado' ? 'pendiente' : 'completado');
        $entityManager->flush();

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/editar', name: 'ticket_editar', methods: ['GET', 'POST'])]
    public function editar(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        TicketRepository $ticketRepository
    ): Response {
        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket no encontrado');
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Ticket actualizado correctamente.');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/editar.html.twig', [
            'form' => $form->createView(),
            'ticket' => $ticket,
        ]);
    }

    /**
     * Obtiene los detalles de un ticket para mostrarlos en el modal de colaboración
     */
    #[Route('/{id}/detalles', name: 'ticket_detalles', methods: ['GET'])]
    public function detalles(int $id, TicketRepository $ticketRepository): JsonResponse
    {
        $ticket = $ticketRepository->find($id);
        
        if (!$ticket) {
            return $this->json([
                'error' => 'Ticket no encontrado',
            ], 404);
        }
        
        return $this->json([
            'id' => $ticket->getId(),
            'ticketId' => $ticket->getTicketId(),
            'descripcion' => $ticket->getDescripcion(),
            'estado' => $ticket->getEstado(),
            'departamento' => $ticket->getDepartamento(),
            'creadoPor' => $ticket->getCreatedBy() ? $ticket->getCreatedBy()->getNombre() . ' ' . $ticket->getCreatedBy()->getApellido() : 'Usuario desconocido',
            'fechaCreacion' => $ticket->getCreatedAt() ? $ticket->getCreatedAt()->format('d/m/Y H:i') : 'Fecha desconocida',
            'colaboradores' => $ticket->getCollaborators()->count(),
        ]);
    }
    
    /**
     * Verifica si un ID de ticket ya existe
     */
    #[Route('/check-id', name: 'ticket_check_id', methods: ['GET'])]
    public function checkTicketId(Request $request, TicketRepository $ticketRepository): JsonResponse
    {
        $ticketId = $request->query->get('ticketId');
        
        if (!$ticketId) {
            return $this->json([
                'exists' => false,
                'message' => 'No se proporcionó un ID de ticket'
            ]);
        }
        
        $ticket = $ticketRepository->findOneBy(['ticketId' => $ticketId]);
        
        return $this->json([
            'exists' => $ticket !== null,
            'ticketId' => $ticket ? $ticket->getId() : null
        ]);
    }
    
    /**
     * Cambia el estado de un ticket
     */
    #[Route('/{id}/estado', name: 'ticket_cambiar_estado', methods: ['POST'])]
    public function cambiarEstado(
        int $id, 
        Request $request, 
        TicketRepository $repo, 
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $ticket = $repo->find($id);
        if (!$ticket) {
            throw $this->createNotFoundException('Ticket no encontrado');
        }

        // Verify user is either creator or collaborator
        if ($ticket->getCreatedBy() !== $user && !$ticket->isCollaborator($user)) {
            throw $this->createAccessDeniedException('No tienes permiso para modificar este ticket');
        }

        // CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cambiar_estado_'.$ticket->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF inválido');
        }

        // Validate new state
        $estado = (string) $request->request->get('estado', '');
        $validos = ['pendiente', 'en proceso', 'terminado', 'rechazado'];
        if (!in_array($estado, $validos, true)) {
            throw new \InvalidArgumentException('Estado inválido');
        }

        $ticket->setEstado($estado);
        $em->flush();

        $this->addFlash('success', 'Estado actualizado correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
}
