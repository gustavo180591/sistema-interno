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
        $stats = [
            'total' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'rejected' => 0,
            'delayed' => 0,
            'completed' => 0,
        ];
        
        if ($user) {
            $queryBuilder = $entityManager->getRepository(Ticket::class)->createQueryBuilder('t');
            
            if ($this->isGranted('ROLE_AUDITOR') || $this->isGranted('ROLE_ADMIN')) {
                // For auditors and admins, show all tickets
                $tickets = $queryBuilder
                    ->orderBy('t.createdAt', 'DESC')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
                
                // Get stats for all tickets
                $statusCounts = $entityManager->createQueryBuilder()
                    ->select('t.status, COUNT(t.id) as count')
                    ->from('App\Entity\Ticket', 't')
                    ->groupBy('t.status')
                    ->getQuery()
                    ->getResult();
                
                $stats['total'] = array_sum(array_column($statusCounts, 'count'));
                foreach ($statusCounts as $status) {
                    $stats[$status['status']] = $status['count'];
                }
                
            } elseif (in_array('ROLE_USER', $user->getRoles(), true)) {
                // For regular users, only show their assigned tickets
                $assignments = $ticketAssignmentRepository->findBy(
                    ['user' => $user],
                    ['assignedAt' => 'DESC'],
                    10 // Limit to 10 most recent
                );
                
                // Extract tickets from assignments
                $tickets = array_map(function($assignment) {
                    return $assignment->getTicket();
                }, $assignments);
                
                // Get stats for user's tickets
                $statusCounts = $entityManager->createQueryBuilder()
                    ->select('t.status, COUNT(t.id) as count')
                    ->from('App\Entity\Ticket', 't')
                    ->join('t.ticketAssignments', 'ta')
                    ->where('ta.user = :user')
                    ->setParameter('user', $user)
                    ->groupBy('t.status')
                    ->getQuery()
                    ->getResult();
                
                $stats['total'] = array_sum(array_column($statusCounts, 'count'));
                foreach ($statusCounts as $status) {
                    $stats[$status['status']] = $status['count'];
                }
            }
            
            // Get recent activities (last 5)
            $recentActivities = $entityManager->createQueryBuilder()
                ->select('t')
                ->from('App\Entity\Ticket', 't')
                ->orderBy('t.updatedAt', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }

        return $this->render('home/index.html.twig', [
            'welcome_message' => 'Panel de Control',
            'tickets' => $tickets,
            'stats' => $stats,
            'recent_activities' => $recentActivities ?? [],
            'is_admin' => $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AUDITOR'),
        ]);
    }
}
