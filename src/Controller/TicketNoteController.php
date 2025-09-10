<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Ticket;
use App\Form\NoteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TicketNoteController extends AbstractController
{
    #[Route('/ticket/{id}/add-note', name: 'ticket_add_note', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addNote(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        // Check if user has permission to add a note to this ticket
        $this->denyAccessUnlessGranted('note', $ticket);
        
        $note = new Note();
        $note->setContent($request->request->get('content'));
        $note->setCreatedBy($this->getUser());
        $note->setTicket($ticket);
        
        $entityManager->persist($note);
        $entityManager->flush();
        
        $this->addFlash('success', 'Nota agregada correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/note/{id}/delete', name: 'ticket_delete_note', methods: ['POST'])]
    public function deleteNote(Note $note, EntityManagerInterface $entityManager): Response
    {
        $ticket = $note->getTicket();
        
        // Check if user is the note creator or has admin role
        if ($note->getCreatedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tienes permiso para eliminar esta nota.');
        }
        
        // Check if user has permission to view the ticket
        $this->denyAccessUnlessGranted('view', $ticket);
        
        $entityManager->remove($note);
        $entityManager->flush();
        
        $this->addFlash('success', 'Nota eliminada correctamente.');
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }
    
    #[Route('/tickets/note/{id}/edit', name: 'ticket_edit_note', methods: ['POST'])]
    #[IsGranted('edit', 'note')]
    public function editNote(Note $note, Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = $request->request->get('content');
        
        if (empty(trim($content))) {
            return $this->json(['success' => false, 'error' => 'El contenido de la nota no puede estar vacío']);
        }
        
        $note->setContent($content);
        $entityManager->persist($note);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'content' => $note->getContent()
        ]);
    }
}
