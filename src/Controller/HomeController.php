<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\TicketAssignmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TicketAssignmentRepository $ticketAssignmentRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tickets = [];
        
        if ($user) {
            if ($this->isGranted('ROLE_AUDITOR') || $this->isGranted('ROLE_ADMIN')) {
                // For auditors and admins, show all tickets
                $tickets = $entityManager->getRepository(Ticket::class)
                    ->createQueryBuilder('t')
                    ->orderBy('t.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } elseif (in_array('ROLE_USER', $user->getRoles(), true)) {
                // For regular users, only show their assigned tickets
                $assignments = $ticketAssignmentRepository->findBy(
                    ['user' => $user],
                    ['assignedAt' => 'DESC']
                );
                
                // Extract tickets from assignments
                $tickets = array_map(function($assignment) {
                    return $assignment->getTicket();
                }, $assignments);
            }
        }

        return $this->render('home/index.html.twig', [
            'welcome_message' => 'Bienvenido al Sistema Interno',
            'tickets' => $tickets,
        ]);
    }
}
