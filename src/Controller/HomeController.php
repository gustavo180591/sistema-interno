<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\TicketAssignmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TicketAssignmentRepository $ticketAssignmentRepository): Response
    {
        $user = $this->getUser();
        $tickets = [];
        
        if ($user && in_array('ROLE_USER', $user->getRoles(), true)) {
            $assignments = $ticketAssignmentRepository->findBy(
                ['user' => $user],
                ['assignedAt' => 'DESC']
            );
            
            // Extract tickets from assignments
            $tickets = array_map(function($assignment) {
                return $assignment->getTicket();
            }, $assignments);
        }

        return $this->render('home/index.html.twig', [
            'welcome_message' => 'Bienvenido al Sistema Interno',
            'tickets' => $tickets,
        ]);
    }
}
