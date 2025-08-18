<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/task/{id}/toggle', name: 'task_toggle_complete', methods: ['POST'])]
    public function toggle(
        ?Task $task, 
        Request $request, 
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        // Return 404 if task not found
        if (!$task) {
            return $this->json(['ok' => false, 'error' => 'task_not_found'], 404);
        }

        // Accept both JSON and form data
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        // Validate required fields
        if (!array_key_exists('completed', $payload)) {
            return $this->json(['ok' => false, 'error' => 'missing_completed'], 400);
        }

        try {
            // Update task status
            $completed = (bool)$payload['completed'];
            $task->setCompleted($completed);
            
            if ($completed) {
                $task->setCompletedAt(new \DateTimeImmutable());
            } else {
                $task->setCompletedAt(null);
            }
            
            $em->flush();

            return $this->json([
                'ok' => true,
                'id' => $task->getId(),
                'completed' => $completed,
                'completedAt' => $task->getCompletedAt() ? $task->getCompletedAt()->format('d/m/Y H:i') : null
            ]);
        } catch (\Throwable $e) {
            // Log the error (uncomment to enable logging)
            // $this->container->get('logger')->error($e->getMessage());
            
            return $this->json([
                'ok' => false, 
                'error' => 'server_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
