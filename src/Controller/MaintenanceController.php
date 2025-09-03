<?php

namespace App\Controller;

use App\Entity\MaintenanceCategory;
use App\Entity\MaintenanceTask;
use App\Entity\MaintenanceLog;
use App\Entity\Machine;
use App\Form\MaintenanceTaskType;
use App\Form\MaintenanceCategoryType;
use App\Repository\MaintenanceCategoryRepository;
use App\Repository\MaintenanceTaskRepository;
use App\Repository\MaintenanceLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maintenance')]
#[IsGranted('ROLE_USER')]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MaintenanceTaskRepository $taskRepository,
        private MaintenanceCategoryRepository $categoryRepository,
        private MaintenanceLogRepository $logRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Test route to verify the controller is working
     */
    #[Route('/test', name: 'maintenance_test')]
    public function test(): Response
    {
        return new Response('Maintenance Controller is working!');
    }

    #[Route('/task/new-from-ticket', name: 'maintenance_task_new_from_ticket', methods: ['POST'], defaults: ['_format' => 'json'])]
    public function newFromTicket(Request $request): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_USER');

            // Log request data for debugging
            error_log('Received request data: ' . print_r($request->request->all(), true));

            // Validate CSRF token
            $token = $request->request->get('_token');
            error_log('CSRF Token received: ' . $token);

            if (!$this->isCsrfTokenValid('submit', $token)) {
                $error = 'Invalid CSRF token. Expected: ' . $this->container->get('security.csrf.token_manager')->getToken('submit')->getValue();
                error_log($error);

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['error' => 'Invalid CSRF token', 'debug' => [
                        'received_token' => $token,
                        'expected_token' => $this->container->get('security.csrf.token_manager')->getToken('submit')->getValue()
                    ]], 403);
                }
                throw $this->createAccessDeniedException($error);
            }

            $scheduledDateStr = $request->request->get('scheduled_date');
            $scheduledTimeStr = $request->request->get('scheduled_time', '09:00');
            $externalTicketId = $request->request->get('external_ticket_id');
            $area = $request->request->get('area');
            $description = $request->request->get('description');

            error_log('Creating task with data:');
            error_log('- Scheduled Date: ' . $scheduledDateStr);
            error_log('- Scheduled Time: ' . $scheduledTimeStr);
            error_log('- External Ticket ID: ' . $externalTicketId);
            error_log('- Area: ' . $area);
            error_log('- Description: ' . $description);

            // Parse date and time (accept ISO "Y-m-dTH:i" or separate date/time)
            $scheduledDateTime = null;
            if (!empty($scheduledDateStr)) {
                if (strpos($scheduledDateStr, 'T') !== false) {
                    // Example: 2025-08-26T09:00
                    $scheduledDateTime = \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDateStr);
                } else {
                    $scheduledDateTime = \DateTime::createFromFormat('Y-m-d H:i', trim($scheduledDateStr . ' ' . $scheduledTimeStr));
                }
                if (!$scheduledDateTime) {
                    // Fallback: try strtotime
                    $ts = strtotime($scheduledDateStr . ' ' . $scheduledTimeStr);
                    if ($ts !== false) {
                        $scheduledDateTime = (new \DateTime())->setTimestamp($ts);
                    }
                }
            }
            if (!$scheduledDateTime) {
                throw new \RuntimeException('Invalid date/time format: date=' . ($scheduledDateStr ?? 'null') . ' time=' . ($scheduledTimeStr ?? 'null'));
            }

            // Create a new maintenance task
            $task = new MaintenanceTask();
            $task->setTitle(sprintf('Ticket %s - %s', $externalTicketId, $area));
            $task->setDescription($description);
            $task->setScheduledDate($scheduledDateTime);
            $task->setStatus('pending');
            // Fields createdBy, externalReference, area were removed from entity; omitting setters.


            // Ensure category exists: try by area; fallback to 'General'; create if missing
            $category = null;
            if (!empty($area)) {
                $category = $this->categoryRepository->findOneBy(['name' => $area]);
            }
            if (!$category) {
                $category = $this->categoryRepository->findOneBy(['name' => 'General']);
            }
            if (!$category) {
                $category = new MaintenanceCategory();
                $category->setName('General');
                $category->setDescription('Categoría por defecto');
                $category->setFrequency('monthly');
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }
            $task->setCategory($category);

            try {
                // Save the task
                $this->entityManager->persist($task);
                $this->entityManager->flush();

                // Add log entry
                $log = new MaintenanceLog();
                $log->setTask($task);
                $log->setUser($this->getUser());
                $log->setType(MaintenanceLog::TYPE_STATUS_CHANGE);
                $log->setMessage('Tarea creada desde ticket externo');
                $this->entityManager->persist($log);
                $this->entityManager->flush();

                error_log('Task created successfully with ID: ' . $task->getId());

                // Always return JSON response
                return $this->json([
                    'success' => true,
                    'task' => [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                        'scheduledDate' => $task->getScheduledDate() ? $task->getScheduledDate()->format('Y-m-d H:i:s') : null,
                        'status' => $task->getStatus(),
                        'description' => $task->getDescription()
                    ]
                ]);

                $this->addFlash('success', 'La tarea ha sido programada exitosamente.');
                return $this->redirectToRoute('maintenance_maintenance_calendar');

            } catch (\Exception $e) {
                error_log('Error creating task: ' . $e->getMessage());
                error_log($e->getTraceAsString());

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Error creating task',
                        'message' => $e->getMessage()
                    ], 500);
                }

                throw $e;
            }

        } catch (\Exception $e) {
            error_log('Unhandled exception in newFromTicket: ' . $e->getMessage());
            error_log($e->getTraceAsString());

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'error' => 'An error occurred',
                    'message' => $e->getMessage()
                ], 500);
            }

            throw $e;
        }
    }


    #[Route('', name: 'maintenance_dashboard')]
    public function dashboard(Request $request): Response
    {
        // Get date range from request or use defaults
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : null;
            
        $endDate = $request->query->get('end_date')
            ? new \DateTime($request->query->get('end_date'))
            : null;
        
        // Get enhanced statistics
        $stats = $this->taskRepository->getTaskStats($startDate, $endDate);
        
        // Get other dashboard data
        $recentTasks = $this->taskRepository->findUpcomingTasks(5);
        $overdueTasks = $this->taskRepository->findOverdueTasks();
        $recentActivity = $this->logRepository->findRecentActivity(10);
        $categories = $this->categoryRepository->findAll();

        return $this->render('maintenance/dashboard.html.twig', [
            'stats' => $stats['overview'],
            'categoryStats' => $stats['categories'],
            'trendStats' => $stats['trends'],
            'recentTasks' => $recentTasks,
            'overdueTasks' => $overdueTasks,
            'recentActivity' => $recentActivity,
            'categories' => $categories,
            'dateRange' => [
                'start' => $startDate ? $startDate->format('Y-m-d') : (new \DateTime('-30 days'))->format('Y-m-d'),
                'end' => $endDate ? $endDate->format('Y-m-d') : (new \DateTime())->format('Y-m-d')
            ]
        ]);
    }

    #[Route('/calendar', name: 'maintenance_calendar')]
    public function calendar(): Response
    {
        $categories = $this->categoryRepository->findAll();

        return $this->render('maintenance/calendar.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/api/calendar/events', name: 'maintenance_calendar_events', methods: ['GET'])]
    public function getCalendarEvents(Request $request): Response
    {
        $start = new \DateTime($request->query->get('start'));
        $end = new \DateTime($request->query->get('end'));
        $showCompleted = $request->query->get('showCompleted', 'true') === 'true';
        $priorities = $request->query->get('priorities', '');
        $categoryId = $request->query->get('category');

        $category = $categoryId ? $this->categoryRepository->find($categoryId) : null;

        $tasks = $this->taskRepository->findTasksForCalendar(
            $start,
            $end,
            $showCompleted,
            $category
        );

        $events = [];
        $now = new \DateTime();

        foreach ($tasks as $task) {
            $isOverdue = $task->getStatus() !== 'completed' &&
                         $task->getScheduledDate() < $now;

            $events[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'start' => $task->getScheduledDate()->format('Y-m-d\TH:i:s'),
                'end' => $task->getCompletedAt() ? $task->getCompletedAt()->format('Y-m-d\TH:i:s') :
                          ($task->getScheduledDate() ? $task->getScheduledDate()->modify('+1 hour')->format('Y-m-d\TH:i:s') : null),
                'allDay' => false,
                'color' => '#6c757d',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus(),
                    'isOverdue' => $isOverdue,
                    'category' => $task->getCategory() ? $task->getCategory()->getName() : null,
                    'assignedTo' => $task->getAssignedTo() ? $task->getAssignedTo()->getFullName() : null,
                ]
            ];
        }

        return $this->json($events);
    }

#[Route('/tasks', name: 'maintenance_tasks')]
    public function tasks(Request $request): Response
    {
        $status = $request->query->get('status');
        $categoryId = $request->query->get('category');
        $assignedTo = $request->query->get('assigned_to');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $sort = $request->query->get('sort', 'scheduledDate');
        $order = $request->query->get('order', 'ASC');

        $filters = [
            'status' => $status,
            'category' => $categoryId,
            'assigned_to' => $assignedTo,
            'date_from' => $dateFrom ? new \DateTime($dateFrom) : null,
            'date_to' => $dateTo ? new \DateTime($dateTo) : null,
            'sort' => $sort,
            'order' => $order,
        ];

        $tasks = $this->taskRepository->findByFilters($filters);
        $categories = $this->categoryRepository->findAll();
        $users = $this->userRepository->findAll();

        return $this->render('maintenance/tasks/list.html.twig', [
            'tasks' => $tasks,
            'categories' => $categories,
            'users' => $users,
            'filters' => $filters,
            'statuses' => MaintenanceTask::getStatuses(),
        ]);
    }

    #[Route('/tasks/new', name: 'maintenance_task_new')]
    public function newTask(Request $request): Response
    {
        $task = new MaintenanceTask();
        
        // Set default values
        $task->setStatus('pending');
        $task->setScheduledDate(new \DateTimeImmutable('+1 day 09:00'));
        
        // Get machine_id from query parameters if present
        $machineId = $request->query->get('machine_id');
        $machine = null;
        
        if ($machineId) {
            $machine = $this->entityManager->getRepository(Machine::class)->find($machineId);
            if ($machine) {
                $task->setTitle('Mantenimiento preventivo - ' . $machine->getInventoryNumber());
                $task->setDescription("Mantenimiento preventivo programado para la máquina " . $machine->getInventoryNumber());
                
                // Set the office from the machine if available
                if ($machine->getOffice()) {
                    $task->setOffice($machine->getOffice());
                }
            }
        }
        
        $form = $this->createForm(MaintenanceTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle file uploads
                $uploadedFiles = $request->files->get('attachments');
                $attachments = [];
                
                if ($uploadedFiles) {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/maintenance';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    foreach ($uploadedFiles as $uploadedFile) {
                        if ($uploadedFile) {
                            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $newFilename = $originalFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
                            
                            $uploadedFile->move(
                                $uploadDir,
                                $newFilename
                            );
                            
                            $attachments[] = $newFilename;
                        }
                    }
                    
                    if (!empty($attachments)) {
                        $task->setAttachments($attachments);
                    }
                }
                
                // Set additional fields from form
                $task->setPriority($request->request->get('priority', 'normal'));
                $task->setEstimatedDuration((int) $request->request->get('estimatedDuration', 0));
                
                // Handle checklist
                $checklist = $request->request->all('checklist');
                if (!empty($checklist)) {
                    $task->setChecklist($checklist);
                }
                
                // Set created by user
                if ($this->getUser()) {
                    $task->setCreatedBy($this->getUser());
                }
                
                // Set machine if available
                if ($machine) {
                    $task->setMachine($machine);
                }
                
                $this->entityManager->persist($task);
                $this->entityManager->flush();
                
                // Log the creation
                $log = new MaintenanceLog();
                $log->setTask($task);
                $log->setUser($this->getUser());
                $log->setType('status_change');
                $log->setMessage('Tarea de mantenimiento creada');
                $this->entityManager->persist($log);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Tarea de mantenimiento creada correctamente.');
                return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al crear la tarea: ' . $e->getMessage());
            }
        }

        return $this->render('maintenance/tasks/new.html.twig', [
            'form' => $form->createView(),
            'machine' => $machine,
            'title' => 'Nueva Tarea de Mantenimiento Preventivo'
        ]);
    }

    #[Route('/tasks/{id}', name: 'maintenance_task_show')]
    public function showTask(MaintenanceTask $task): Response
    {
        $logs = $this->logRepository->findByTask($task);

        return $this->render('maintenance/tasks/show.html.twig', [
            'task' => $task,
            'logs' => $logs,
        ]);
    }

    #[Route('/tasks/{id}/comment', name: 'maintenance_task_comment', methods: ['POST'])]
    public function commentTask(Request $request, MaintenanceTask $task): Response
    {
        $comment = trim((string) $request->request->get('comment', ''));
        if ($comment !== '') {
            $this->logRepository->addComment($task, $comment, $this->getUser());
        } else {
            $this->addFlash('error', 'El comentario no puede estar vacío.');
        }

        return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
    }

    #[Route('/tasks/{id}/edit', name: 'maintenance_task_edit')]
    public function editTask(Request $request, MaintenanceTask $task): Response
    {
        $form = $this->createForm(MaintenanceTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Tarea actualizada correctamente.');
            return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
        }

        return $this->render('maintenance/tasks/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tasks/{id}/complete', name: 'maintenance_task_complete', methods: ['POST'])]
    public function completeTask(MaintenanceTask $task): Response
    {
        $task->setStatus(MaintenanceTask::STATUS_COMPLETED);
        $task->setCompletedAt(new \DateTimeImmutable());
        $task->setCompletedBy($this->getUser());

        $this->entityManager->flush();

        $this->logRepository->logCompletion($task, $this->getUser());

        $this->addFlash('success', 'Tarea marcada como completada.');
        return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
    }

    #[Route('/categories', name: 'maintenance_categories')]
    public function categories(): Response
    {
        $categories = $this->categoryRepository->findAll();

        return $this->render('maintenance/categories/list.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/new', name: 'maintenance_category_new')]
    public function newCategory(Request $request): Response
    {
        $category = new MaintenanceCategory();
        $form = $this->createForm(MaintenanceCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Categoría creada correctamente.');
            return $this->redirectToRoute('maintenance_maintenance_categories');
        }

        return $this->render('maintenance/categories/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'maintenance_category_edit')]
    public function editCategory(Request $request, MaintenanceCategory $category): Response
    {
        $form = $this->createForm(MaintenanceCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Categoría actualizada correctamente.');
            return $this->redirectToRoute('maintenance_maintenance_categories');
        }

        return $this->render('maintenance/categories/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'maintenance_category_delete', methods: ['DELETE'])]
    public function deleteCategory(MaintenanceCategory $category): Response
    {
        try {
            // Check if category has associated tasks
            if ($category->getTasks()->count() > 0) {
                $this->addFlash('error', 'No se puede eliminar la categoría porque tiene tareas asociadas. Primero debe eliminar o reasignar las tareas.');
                return $this->redirectToRoute('maintenance_maintenance_categories');
            }

            $this->entityManager->remove($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Categoría eliminada correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error al eliminar la categoría: ' . $e->getMessage());
        }

        return $this->redirectToRoute('maintenance_maintenance_categories');
    }

    #[Route('/tasks/{id}/details', name: 'maintenance_task_details', methods: ['GET'])]
    public function taskDetails(MaintenanceTask $task): Response
    {
        return $this->render('maintenance/tasks/_task_details.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/delete', name: 'maintenance_task_delete', methods: ['POST', 'DELETE'])]
    public function deleteTask(Request $request, MaintenanceTask $task): Response
    {
        $submittedToken = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete' . $task->getId(), $submittedToken)) {
            $this->addFlash('error', 'Token CSRF inválido.');
            return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
        }

        try {
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tarea eliminada correctamente.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'No se pudo eliminar la tarea: ' . $e->getMessage());
            return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
        }

        return $this->redirectToRoute('maintenance_maintenance_tasks');
    }

    #[Route('/tasks/{id}/reopen', name: 'maintenance_task_reopen', methods: ['POST'])]
    public function reopenTask(Request $request, MaintenanceTask $task): Response
    {
        $newStatus = (string) $request->request->get('status', MaintenanceTask::STATUS_PENDING);
        $newDateStr = (string) $request->request->get('scheduledDate', '');

        // Parse new date
        $newDate = null;
        if ($newDateStr !== '') {
            $newDate = \DateTime::createFromFormat('Y-m-d\TH:i', $newDateStr) ?: new \DateTime($newDateStr);
        }

        $oldStatus = $task->getStatus();
        $task->setStatus($newStatus);
        if ($newDate instanceof \DateTimeInterface) {
            $task->setScheduledDate(\DateTimeImmutable::createFromMutable($newDate));
        }
        $task->setCompletedAt(null);
        $task->setCompletedBy(null);

        $this->entityManager->flush();

        // Log status change
        $this->logRepository->logStatusChange($task, $oldStatus, $newStatus, $this->getUser());

        $this->addFlash('success', 'Tarea reabierta correctamente.');
        return $this->redirectToRoute('maintenance_maintenance_task_show', ['id' => $task->getId()]);
    }

    #[Route('/reports', name: 'maintenance_reports')]
    public function reports(): Response
    {
        // Generate reports data
        $stats = $this->taskRepository->getTaskStats();
        $categories = $this->categoryRepository->findWithTaskCounts();

        return $this->render('maintenance/reports/index.html.twig', [
            'stats' => $stats,
            'categories' => $categories,
        ]);
    }
}
