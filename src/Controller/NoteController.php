<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Ticket;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NoteController extends AbstractController
{
    #[Route('/notes/add/{ticketId}', name: 'note_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        int $ticketId,
        Request $request,
        EntityManagerInterface $em,
        NoteRepository $noteRepository
    ): JsonResponse {
        $content = $request->request->get('content');
        
        if (empty(trim($content))) {
            return $this->json([
                'success' => false,
                'error' => 'El contenido de la nota no puede estar vacío'
            ], 400);
        }

        $ticket = $em->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket) {
            return $this->json([
                'success' => false,
                'error' => 'No se encontró el ticket especificado'
            ], 404);
        }

        $note = new Note();
        $note->setContent($content);
        $note->setCreatedBy($this->getUser());
        $note->setCreatedAt(new \DateTimeImmutable());
        $note->setTicket($ticket);

        $em->persist($note);
        $em->flush();

        return $this->json([
            'success' => true,
            'noteId' => $note->getId(),
            'content' => $note->getContent(),
            'createdAt' => $note->getCreatedAt()->format('Y-m-d H:i:s'),
            'createdBy' => [
                'id' => $this->getUser()->getId(),
                'name' => $this->getUser()->getNombre() . ' ' . $this->getUser()->getApellido()
            ],
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'isOwner' => true
        ]);
    }

    #[Route('/notes/delete/{id}', name: 'note_delete', methods: ['POST'])]
    public function delete(
        Note $note,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('delete', $note);

        if (!$this->isCsrfTokenValid('delete' . $note->getId(), $request->request->get('_token'))) {
            return $this->json([
                'success' => false,
                'error' => 'Token CSRF inválido'
            ], 400);
        }

        $ticketId = $note->getTicket()->getId();
        $noteId = $note->getId();
        
        $em->remove($note);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'noteId' => $noteId,
            'ticketId' => $ticketId
        ]);
    }
}
