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
        // Debug: Log user authentication status
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'ok' => false, 
                'error' => 'Usuario no autenticado',
                'debug' => 'No hay usuario en la sesión'
            ], 401);
        }

        if (!$task) {
            return new JsonResponse(['ok' => false, 'error' => 'Tarea no encontrada'], 404);
        }

        // Get token from request
        $content = $request->getContent();
        $data = [];
        if (!empty($content)) {
            $data = json_decode($content, true) ?? [];
        }
        
        $token = $request->request->get('_token') 
            ?? $request->headers->get('X-CSRF-Token') 
            ?? ($data['_token'] ?? null);
        
        if (!$this->isCsrfTokenValid('app', $token)) {
            return new JsonResponse([
                'ok' => false, 
                'error' => 'Token CSRF inválido',
                'received_token' => $token ? 'Token received' : 'No token received',
                'request_data' => $request->request->all(),
                'headers' => $request->headers->all()
            ], 403);
        }

        // Security: Check if user has access to the ticket
        try {
            $this->denyAccessUnlessGranted('edit', $task->getTicket());
        } catch (\Exception $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Acceso denegado: ' . $e->getMessage(),
                'user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'No autenticado',
                'ticket_id' => $task->getTicket()->getId()
            ], 403);
        }

        try {
            // Mark task as completed
            $task->setCompleted(true);
            $task->setCompletedAt(new \DateTimeImmutable('now', new \DateTimeZone('America/Argentina/Buenos_Aires')));
            
            $em->flush();

            return new JsonResponse([
                'ok' => true,
                'completed' => true,
                'completedAt' => $task->getCompletedAt()->format('d/m/Y H:i')
            ]);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Error al actualizar la tarea',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/task/{id}', name: 'task_delete', methods: ['DELETE'])]
    public function delete(
        ?Task $task,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Debug: Log user authentication status
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'ok' => false, 
                'error' => 'Usuario no autenticado',
                'debug' => 'No hay usuario en la sesión'
            ], 401);
        }

        if (!$task) {
            return new JsonResponse(['ok' => false, 'error' => 'Tarea no encontrada'], 404);
        }

        // Get token from request
        $content = $request->getContent();
        $data = [];
        if (!empty($content)) {
            $data = json_decode($content, true) ?? [];
        }
        
        $token = $request->request->get('_token') 
            ?? $request->headers->get('X-CSRF-Token') 
            ?? ($data['_token'] ?? null);
        
        if (!$this->isCsrfTokenValid('app', $token)) {
            return new JsonResponse([
                'ok' => false, 
                'error' => 'Token CSRF inválido',
                'received_token' => $token ? 'Token received' : 'No token received',
                'request_data' => $request->request->all(),
                'headers' => $request->headers->all()
            ], 403);
        }

        // Security: Check if user has access to the ticket
        try {
            $this->denyAccessUnlessGranted('edit', $task->getTicket());
        } catch (\Exception $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Acceso denegado: ' . $e->getMessage(),
                'user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'No autenticado',
                'ticket_id' => $task->getTicket()->getId()
            ], 403);
        }

        try {
            $em->remove($task);
            $em->flush();

            return new JsonResponse([
                'ok' => true,
                'message' => 'Tarea eliminada correctamente'
            ]);
            
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Error al eliminar la tarea',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
