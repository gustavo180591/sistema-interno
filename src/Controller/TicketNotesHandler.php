<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Ticket;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles all ticket note related operations
 */
class TicketNotesHandler extends AbstractController
{
    #[Route('/api/tickets/notes/add/{ticketId}', name: 'api_ticket_note_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        int $ticketId,
        Request $request,
        EntityManagerInterface $em,
        NoteRepository $noteRepository
    ): JsonResponse {
        // Get the ticket and check permissions
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket) {
            return $this->json([
                'success' => false,
                'error' => 'Ticket no encontrado.'
            ], 404);
        }

        // Check if user has permission to add a note to this ticket
        $this->denyAccessUnlessGranted('note', $ticket);

        $content = $request->request->get('content');
        if (empty(trim($content))) {
            return $this->json([
                'success' => false,
                'error' => 'El contenido de la nota no puede estar vacío.'
            ], 400);
        }

        $note = new Note();
        $note->setContent($content);
        $note->setCreatedBy($this->getUser());
        $note->setTicket($ticket);
        $note->setCreatedAt(new \DateTimeImmutable());

        $em->persist($note);
        $em->flush();

        return $this->json([
            'success' => true,
            'note' => [
                'id' => $note->getId(),
                'content' => $note->getContent(),
                'createdAt' => $note->getCreatedAt()->format('d/m/Y H:i'),
                'createdBy' => [
                    'id' => $note->getCreatedBy()->getId(),
                    'username' => $note->getCreatedBy()->getUsername(),
                ]
            ]
        ]);
    }

    #[Route('/api/tickets/notes/delete/{id}', name: 'api_ticket_note_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Note $note,
        EntityManagerInterface $em
    ): JsonResponse {
        // Check if user has permission to delete this note
        $this->denyAccessUnlessGranted('delete', $note);

        $em->remove($note);
        $em->flush();

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/api/tickets/notes/edit/{id}', name: 'api_ticket_note_edit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Note $note,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Check if user has permission to edit this note
        $this->denyAccessUnlessGranted('edit', $note);

        $content = $request->request->get('content');
        if (empty(trim($content))) {
            return $this->json([
                'success' => false,
                'error' => 'El contenido de la nota no puede estar vacío.'
            ], 400);
        }

        $note->setContent($content);
        $em->flush();

        return $this->json([
            'success' => true,
            'content' => $note->getContent()
        ]);
    }
}
