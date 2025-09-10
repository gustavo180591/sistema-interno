<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

require __DIR__.'/vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$tokenStorage = $container->get('security.token_storage');
$token = $tokenStorage->getToken();

if ($token) {
    $user = $token->getUser();
    
    if ($user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
        echo "User: " . $user->getUserIdentifier() . "\n";
        echo "Roles: " . implode(', ', $user->getRoles()) . "\n";
    } else {
        echo "No user is currently logged in.\n";
    }
} else {
    echo "No authentication token found.\n";
}

echo "\nIs granted ROLE_ADMIN: " . ($container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? 'YES' : 'NO') . "\n";
echo "Is granted ROLE_AUDITOR: " . ($container->get('security.authorization_checker')->isGranted('ROLE_AUDITOR') ? 'YES' : 'NO') . "\n";

echo "\nAvailable routes for maintenance:\n";
$router = $container->get('router');
$routes = $router->getRouteCollection();

foreach ($routes as $routeName => $route) {
    if (strpos($routeName, 'maintenance_') === 0) {
        echo "- $routeName: " . $route->getPath() . "\n";
    }
}
