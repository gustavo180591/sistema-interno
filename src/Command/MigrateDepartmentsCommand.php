<?php

namespace App\Command;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-departments',
    description: 'Migra los departamentos antiguos a los nuevos de la Municipalidad de Posadas',
)]
class MigrateDepartmentsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Migración de Departamentos - Municipalidad de Posadas');
        $io->text('Migrando departamentos antiguos a los nuevos...');

        // Mapeo de departamentos antiguos a nuevos
        $mapping = [
            1 => 8,  // Sistemas -> Dirección de Gestión y TIC
            2 => 6,  // Administración -> Dirección General de Administración y Contabilidad
            3 => 11, // Recursos Humanos -> Dirección de RR.HH
            4 => 13, // Contabilidad -> Dirección de Contabilidad y Presupuesto
            5 => 15, // Ventas -> Dirección de Abastecimiento
            6 => 18, // Atención al Cliente -> Dirección de RR.PP y Ceremonial
            7 => 17, // Logística -> Dirección de Obras e Infraestructura
            8 => 23, // Almacén -> Departamento de Bienes Patrimoniales
            9 => 22, // Compras -> Departamento de Compras y Licitaciones
            10 => 1, // Dirección -> Presidencia
        ];

        $ticketRepository = $this->entityManager->getRepository(Ticket::class);
        $tickets = $ticketRepository->findAll();

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($tickets as $ticket) {
            $oldDepartment = $ticket->getDepartamento();
            
            if (isset($mapping[$oldDepartment])) {
                $newDepartment = $mapping[$oldDepartment];
                $ticket->setDepartamento($newDepartment);
                $migratedCount++;
                
                $io->text(sprintf(
                    'Migrado: Ticket #%s: %s -> %s',
                    $ticket->getTicketId(),
                    $this->getOldDepartmentName($oldDepartment),
                    $this->getNewDepartmentName($newDepartment)
                ));
            } else {
                $skippedCount++;
                $io->text(sprintf(
                    'Omitido: Ticket #%s: Departamento %s no requiere migración',
                    $ticket->getTicketId(),
                    $oldDepartment
                ));
            }
        }

        if ($migratedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Migración completada. %d tickets migrados, %d omitidos.', $migratedCount, $skippedCount));
        } else {
            $io->info('No se encontraron tickets que requieran migración.');
        }

        return Command::SUCCESS;
    }

    private function getOldDepartmentName(int $id): string
    {
        $oldNames = [
            1 => 'Sistemas',
            2 => 'Administración',
            3 => 'Recursos Humanos',
            4 => 'Contabilidad',
            5 => 'Ventas',
            6 => 'Atención al Cliente',
            7 => 'Logística',
            8 => 'Almacén',
            9 => 'Compras',
            10 => 'Dirección',
        ];

        return $oldNames[$id] ?? 'Desconocido';
    }

    private function getNewDepartmentName(int $id): string
    {
        $newNames = [
            1 => 'Presidencia',
            2 => 'Secretaría',
            3 => 'Prosecretaria Legislativa',
            4 => 'Prosecretaria Administrativa',
            5 => 'Dirección General de Gestión Financiera y Administrativa',
            6 => 'Dirección General de Administración y Contabilidad',
            7 => 'Dirección General de Asuntos Legislativos y Comisiones',
            8 => 'Dirección de Gestión y TIC',
            9 => 'Dirección de Desarrollo Humano',
            10 => 'Dirección de Personal',
            11 => 'Dirección de RR.HH',
            12 => 'Dirección de Asuntos Jurídicos',
            13 => 'Dirección de Contabilidad y Presupuesto',
            14 => 'Dirección de Liquidación de Sueldos',
            15 => 'Dirección de Abastecimiento',
            16 => 'Dirección de Salud Mental',
            17 => 'Dirección de Obras e Infraestructura',
            18 => 'Dirección de RR.PP y Ceremonial',
            19 => 'Dirección de Digesto Jurídico',
            20 => 'Dirección de Prensa',
            21 => 'Departamento de Archivos',
            22 => 'Departamento de Compras y Licitaciones',
            23 => 'Departamento de Bienes Patrimoniales',
            24 => 'Departamento de Cómputos',
            25 => 'Departamento de Reconocimiento Médico',
            26 => 'Departamento de Asuntos Legislativos',
            27 => 'Departamento de Comisiones',
            28 => 'Departamento de Mesa de Entradas y Salidas',
            29 => 'Departamento de Sumario',
            30 => 'División Presupuesto y Rendición de Cuentas',
            31 => 'División Cuota Alimentaria y EMB. JUD.',
            32 => 'Sección Computos',
            33 => 'Sección Previsional',
            34 => 'Sección Sumario',
            35 => 'Sección Liquidación de Sueldos y Jornales',
            36 => 'Sección Suministro',
            37 => 'Sección Servicios Generales',
            38 => 'Sección Legajo y Archivo',
            39 => 'Sección Seguridad',
            40 => 'Sección Mantenimiento',
            41 => 'Sección Cuerpo Taquígrafos',
            42 => 'Sección Biblioteca',
            43 => 'Coordinación de Jurídico y Administración',
            44 => 'Agenda HCD',
            45 => 'Municipalidad de Posadas',
            46 => 'Defensora del Pueblo',
            47 => 'Concejal Dib Jair',
            48 => 'Concejal Velazquez Pablo',
            49 => 'Concejal Traid Laura',
            50 => 'Concejal Scromeda Luciana',
            51 => 'Concejal Salom Judith',
            52 => 'Concejal Mazal Malena',
            53 => 'Concejal Martinez Horacio',
            54 => 'Concejal Koch Santiago',
            55 => 'Concejal Jimenez Eva',
            56 => 'Concejal Gomez Valeria',
            57 => 'Concejal Cardozo Hector',
            58 => 'Concejal Argañaraz Pablo',
            59 => 'Concejal Almiron Samira',
            60 => 'Concejal Dardo Romero',
        ];

        return $newNames[$id] ?? 'Desconocido';
    }
} 