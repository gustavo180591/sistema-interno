<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

class EntityNotFoundListener
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Only handle NotFoundHttpException
        if (!$exception instanceof NotFoundHttpException) {
            return;
        }

        $message = $exception->getMessage();
        
        // Check if this is a "not found" exception for an entity
        if (str_contains($message, 'object not found by')) {
            $response = new Response(
                $this->twig->render('error/404.html.twig', [
                    'status_code' => 404,
                    'status_text' => 'Recurso no encontrado',
                    'message' => 'El recurso solicitado no existe o ha sido eliminado.'
                ]),
                Response::HTTP_NOT_FOUND
            );
            
            $event->setResponse($response);
        }
    }
}
