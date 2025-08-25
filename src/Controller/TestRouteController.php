<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestRouteController extends AbstractController
{
    #[Route('/test-route', name: 'test_route')]
    public function index(): Response
    {
        return new Response('Test route is working!');
    }
}
