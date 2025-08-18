<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/task/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
    public function toggle(
        ?Task $task, 
        Request $request, 
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$task) {
            return $this->json(['ok' => false, 'error' => 'Task not found'], 404);
        }

        // Parse request body
        $payload = json_decode($request->getContent() ?: '[]', true);
        $completed = array_key_exists('completed', $payload)
            ? (bool) $payload['completed']
            : !$task->isCompleted();

        try {
            // Update task
            $task->setCompleted($completed);
            if ($completed) {
                $task->setCompletedAt(new \DateTimeImmutable());
            } else {
                $task->setCompletedAt(null);
            }
            
            $em->flush();

            return $this->json([
                'ok' => true,
                'completed' => $task->isCompleted(),
                'completedAt' => $task->getCompletedAt() ? $task->getCompletedAt()->format('d/m/Y H:i') : null
            ]);
            
        } catch (\Throwable $e) {
            // Log error (uncomment to enable logging)
            // $this->container->get('logger')->error($e->getMessage());
            
            return $this->json([
                'ok' => false,
                'error' => 'db_error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
