<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Ticket;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ): Response {
        $content = $request->request->get('content');
        
        if (empty(trim($content))) {
            $this->addFlash('error', 'El contenido de la nota no puede estar vacío');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }

        $note = new Note();
        $note->setContent($content);
        $note->setCreatedBy($this->getUser());
        $note->setCreatedAt(new \DateTimeImmutable());
        
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket) {
            $this->addFlash('error', 'No se encontró el ticket especificado');
            return $this->redirectToRoute('ticket_index');
        }
        $note->setTicket($ticket);

        $em->persist($note);
        $em->flush();

        $this->addFlash('success', 'Nota agregada correctamente');
        return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
    }

    #[Route('/notes/delete/{id}', name: 'note_delete', methods: ['POST'])]
    public function delete(
        Note $note,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('delete', $note);

        if ($this->isCsrfTokenValid('delete' . $note->getId(), $request->request->get('_token'))) {
            $ticketId = $note->getTicket()->getId();
            $em->remove($note);
            $em->flush();
            
            $this->addFlash('success', 'Nota eliminada correctamente');
            return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
        }

        $this->addFlash('error', 'Token CSRF inválido');
        return $this->redirectToRoute('ticket_show', ['id' => $note->getTicket()->getId()]);
    }
}
