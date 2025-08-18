<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketControllerTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        
        // Retrieve the test user (adjust the email to match a test user in your database)
        $this->testUser = $this->userRepository->findOneByEmail('admin@example.com');
        
        // Simulate logging in the user
        $this->client->loginUser($this->testUser);
    }
    public function testTicketListPageLoads(): void
    {
        $this->client->request('GET', '/ticket/lista');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertSelectorExists('h1'); // Check for a heading that indicates the ticket list
    }

    public function testFilterByStatus(): void
    {
        $this->client->request('GET', '/ticket/lista?estado=pendiente');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'pendiente');
        // Additional check to ensure only tickets with status 'pendiente' are shown
        $this->assertSelectorNotExists('tr.ticket-row:not(:contains("pendiente"))');
    }

    public function testFilterByDepartment(): void
    {
        // First, get the department ID from the page or use a known test department ID
        $crawler = $this->client->request('GET', '/ticket/lista');
        $departmentId = 1; // Default fallback
        
        // Try to find a department link and extract its ID
        $departmentLink = $crawler->filter('a[href*="departamento="]')->first();
        if ($departmentLink->count() > 0) {
            $departmentHref = $departmentLink->attr('href');
            preg_match('/departamento=(\d+)/', $departmentHref, $matches);
            if (isset($matches[1])) {
                $departmentId = $matches[1];
            }
        }
        
        $this->client->request('GET', "/ticket/lista?departamento=$departmentId");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.ticket-row');
    }

    public function testDateRangeFilter(): void
    {
        // Use a date range that should include some test data
        $this->client->request('GET', '/ticket/lista?fechaDesde=2024-01-01&fechaHasta=2026-12-31');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.ticket-row');
        
        // Test with a date range that should return no results
        $this->client->request('GET', '/ticket/lista?fechaDesde=2099-01-01&fechaHasta=2099-12-31');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.ticket-row');
    }

    public function testCombinedFilters(): void
    {
        // First, get some test data to work with
        $crawler = $this->client->request('GET', '/ticket/lista');
        
        // Get a search term that exists in the first ticket
        $searchTerm = '';
        $firstTicket = $crawler->filter('.ticket-row')->first();
        if ($firstTicket->count() > 0) {
            // Try to get some text from the ticket to use as search term
            $searchTerm = substr($firstTicket->text(), 0, 5);
        }
        
        // Get a department ID that exists
        $departmentId = 1; // Default fallback
        $departmentLink = $crawler->filter('a[href*="departamento="]')->first();
        if ($departmentLink->count() > 0) {
            $departmentHref = $departmentLink->attr('href');
            preg_match('/departamento=(\d+)/', $departmentHref, $matches);
            if (isset($matches[1])) {
                $departmentId = $matches[1];
            }
        }
        
        // Test with combined filters
        $this->client->request('GET', "/ticket/lista?search=$searchTerm&estado=pendiente&departamento=$departmentId");
        $this->assertResponseIsSuccessful();
        
        // If we have search results, verify at least one row is shown
        if ($this->client->getResponse()->getStatusCode() === 200) {
            $this->assertSelectorExists('.ticket-row');
        } else {
            $this->markTestSkipped('No test data available for combined filters test');
        }
    }
}
