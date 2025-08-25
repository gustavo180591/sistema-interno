<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Get kernel
$kernel = new App\Kernel('dev', true);
$kernel->boot();

// Create application
$application = new Application($kernel);
$application->setAutoExit(false);

// Create database if it doesn't exist
$output = new ConsoleOutput();
$output->writeln('Creating database if it does not exist...');
$input = new ArrayInput([
    'command' => 'doctrine:database:create',
    '--if-not-exists' => true,
]);
$application->run($input, $output);

// Run migrations
$output->writeln('Running migrations...');
$input = new ArrayInput([
    'command' => 'doctrine:migrations:migrate',
    '--no-interaction' => true,
    '--allow-no-migration' => true,
]);
$application->run($input, $output);

// Create admin user
$output->writeln('Creating admin user...');
$input = new ArrayInput([
    'command' => 'app:create-admin',
    'email' => 'admin@example.com',
    'password' => 'admin123',
    'username' => 'admin',
    'firstname' => 'Admin',
    'lastname' => 'User',
]);
$application->run($input, $output);

$output->writeln('Setup completed successfully!');
