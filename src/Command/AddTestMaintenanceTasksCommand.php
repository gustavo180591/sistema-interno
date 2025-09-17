<?php

namespace App\Command;

use App\Entity\MaintenanceTask;
use App\Entity\User;
use App\Entity\MaintenanceCategory;
use App\Entity\Office;
use App\Entity\Machine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-test-tasks',
    description: 'Add test maintenance tasks',
)]
class AddTestMaintenanceTasksCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get existing users (assuming there are some)
        $users = $this->entityManager->getRepository(User::class)->findAll();
        if (empty($users)) {
            $io->error('No users found. Please create users first.');
            return Command::FAILURE;
        }

        // Create a test category if none exists
        $category = $this->entityManager->getRepository(MaintenanceCategory::class)->findOneBy([]);
        if (!$category) {
            $category = new MaintenanceCategory();
            $category->setName('Test Category');
            $category->setDescription('Test Category Description');
            $category->setFrequency('monthly'); // Set a default frequency
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        }

        // Create a test office if none exists
        $office = $this->entityManager->getRepository(Office::class)->findOneBy([]);
        if (!$office) {
            $office = new Office();
            $office->setName('Test Office');
            $office->setLocation('Test Location');
            $this->entityManager->persist($office);
            $this->entityManager->flush();
        }

        // Create a test machine if none exists
        $machine = $this->entityManager->getRepository(Machine::class)->findOneBy([]);
        if (!$machine) {
            $machine = new Machine();
            $machine->setInventoryNumber('TEST-123');
            $machine->setCpu('Test CPU');
            $machine->setOs('Test OS');
            $machine->setOffice($office);
            $machine->setRamGb(8);
            $machine->setInstitutional(true);
            $machine->setDisk('500GB SSD');
            $this->entityManager->persist($machine);
            $this->entityManager->flush();
        }

        // Create test tasks
        $statuses = [
            MaintenanceTask::STATUS_PENDING,
            MaintenanceTask::STATUS_IN_PROGRESS,
            MaintenanceTask::STATUS_COMPLETED,
            MaintenanceTask::STATUS_OVERDUE
        ];

        $now = new \DateTimeImmutable();
        
        for ($i = 1; $i <= 10; $i++) {
            $task = new MaintenanceTask();
            $task->setTitle('Test Task ' . $i);
            $task->setDescription('This is a test maintenance task #' . $i);
            $task->setStatus($statuses[array_rand($statuses)]);
            $task->setAssignedTo($users[array_rand($users)]);
            $task->setCategory($category);
            $task->setOffice($office);
            $task->setMachine($machine);
            $task->setScheduledDate($now->modify('+' . $i . ' days'));
            
            if (in_array($task->getStatus(), [MaintenanceTask::STATUS_COMPLETED])) {
                $task->setCompletedAt($now->modify('+' . $i . ' hours'));
            }
            
            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        $io->success('Added 10 test maintenance tasks.');
        return Command::SUCCESS;
    }
}
