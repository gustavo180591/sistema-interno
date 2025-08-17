<?php
// src/Controller/TicketController.php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    #[Route('/lista', name: 'ticket_lista')]
    public function index(TicketRepository $repo): Response
    {
        $tickets = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/nuevo', name: 'ticket_nuevo')]
    public function nuevo(Request $request, EntityManagerInterface $em): Response
    {
        $ticket = new Ticket();
        $ticket->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ticket);
            $em->flush();

            $this->addFlash('success', '✅ Ticket creado correctamente.');
            return $this->redirectToRoute('ticket_lista');
        }

        return $this->render('ticket/nuevo.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mis-tickets', name: 'app_ticket')]
    public function misTickets(TicketRepository $repo): Response
    {
        return $this->redirectToRoute('ticket_lista');
    }
}
