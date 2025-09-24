<?php

namespace App\Controller\Api;

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
 * API controller for managing ticket notes
 */
#[Route('/api/tickets/notes')]
class TicketNotesApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NoteRepository $noteRepository
    ) {
    }

    #[Route('/add/{ticketId}', name: 'api_ticket_note_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(int $ticketId, Request $request): JsonResponse
    {
        // Get the ticket and check permissions
        $ticket = $this->em->getRepository(Ticket::class)->find($ticketId);
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

        $this->em->persist($note);
        $this->em->flush();

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

    #[Route('/delete/{id}', name: 'api_ticket_note_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Note $note): JsonResponse
    {
        // Check if user has permission to delete this note
        $this->denyAccessUnlessGranted('delete', $note);

        $this->em->remove($note);
        $this->em->flush();

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/edit/{id}', name: 'api_ticket_note_edit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Note $note, Request $request): JsonResponse
    {
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
        $this->em->flush();

        return $this->json([
            'success' => true,
            'content' => $note->getContent()
        ]);
    }
}
