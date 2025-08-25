<?php

require __DIR__.'/vendor/autoload.php';

// Create a request to simulate the web environment
$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = true;

$kernel = new App\Kernel('dev', true);
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);

// Get the security context
$container = $kernel->getContainer();

// Check if the security token is available
$token = $container->get('security.token_storage')->getToken();

if ($token && $token->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface) {
    $user = $token->getUser();
    echo "Current user: " . $user->getUserIdentifier() . "\n";
    echo "Roles: " . implode(', ', $user->getRoles()) . "\n";
    
    // Check specific roles
    $checker = $container->get('security.authorization_checker');
    echo "Has ROLE_ADMIN: " . ($checker->isGranted('ROLE_ADMIN') ? 'YES' : 'NO') . "\n";
    echo "Has ROLE_AUDITOR: " . ($checker->isGranted('ROLE_AUDITOR') ? 'YES' : 'NO') . "\n";
    
    // Check access to maintenance routes
    $router = $container->get('router');
    $maintenanceRoutes = [
        'maintenance_dashboard',
        'maintenance_tasks',
        'maintenance_calendar',
        'maintenance_categories'
    ];
    
    echo "\nMaintenance routes access check:\n";
    foreach ($maintenanceRoutes as $route) {
        try {
            $path = $router->generate($route);
            echo "- $route ($path): " . ($checker->isGranted('ROLE_ADMIN') ? 'ACCESS GRANTED' : 'ACCESS DENIED') . "\n";
        } catch (Exception $e) {
            echo "- $route: ROUTE NOT FOUND\n";
        }
    }
} else {
    echo "No user is currently logged in.\n";
}

$kernel->terminate($request, $response);
