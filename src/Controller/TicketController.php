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
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketRepository $ticketRepository,
        private UserRepository $userRepository
    ) {
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

    #[Route('/tickets/{id}/update-status', name: 'ticket_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function updateStatus(Request $request, Ticket $ticket): Response
    {
        $newStatus = $request->request->get('status');
        
        try {
            $ticket->setStatus($newStatus);
            $this->entityManager->flush();
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Estado no válido.');
        }
        
        return $this->redirectToRoute('ticket_index');
    }

    #[Route('/tickets/{id}/assign', name: 'ticket_assign', methods: ['POST'])]
    #[IsGranted('ROLE_AUDITOR')]
    public function assign(Request $request, Ticket $ticket): Response
    {
        $assignedUserIds = $request->request->all('assigned_users');
        $dueDate = new \DateTime($request->request->get('due_date'));
        
        if (empty($assignedUserIds)) {
            $this->addFlash('error', 'Debe seleccionar al menos un usuario para asignar el ticket.');
            return $this->redirectToRoute('ticket_index');
        }
        
        $assignedUsers = $this->entityManager->getRepository(User::class)->findBy(['id' => $assignedUserIds]);
        
        if (count($assignedUsers) !== count($assignedUserIds)) {
            $this->addFlash('error', 'Uno o más usuarios no fueron encontrados.');
            return $this->redirectToRoute('ticket_index');
        }
        
        // Clone the ticket for each assigned user
        foreach ($assignedUsers as $user) {
            if ($user === $ticket->getCreatedBy()) {
                continue; // Skip if the user is the creator of the ticket
            }
            
            $ticketClone = clone $ticket;
            $ticketClone->setAssignedTo($user);
            $ticketClone->setDueDate(clone $dueDate);
            $ticketClone->setCreatedAt(new \DateTime());
            $ticketClone->setStatus('pending'); // Reset status for the new assignment
            
            $this->entityManager->persist($ticketClone);
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Ticket asignado exitosamente a los usuarios seleccionados.');
        return $this->redirectToRoute('ticket_index');
    }

    #[Route('/tickets/{id}', name: 'ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
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
