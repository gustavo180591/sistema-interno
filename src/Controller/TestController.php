<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * Test route to verify basic routing is working
     */
    #[Route('/test-route', name: 'test_route')]
    public function test(): Response
    {
        return new Response('Test route is working!');
    }
    
    /**
     * Test route to verify calendar events functionality
     */
    #[Route('/test/calendar', name: 'test_calendar')]
    public function testCalendar(): Response
    {
        return $this->render('test/simple_test.html.twig');
    }
}
