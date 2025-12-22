<?php

namespace App\Command;

use App\Entity\Office;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-offices',
    description: 'Update offices to match the new structure',
)]
class UpdateOfficesCommand extends Command
{
    private $entityManager;

    // New office structure
    private $newOffices = [
        // Concejales
        'Concejal Almiron Samira' => 'Concejal Almiron Samira',
        'Concejal Argañaraz Pablo' => 'Concejal Argañaraz Pablo',
        'Concejal Cardozo Hector' => 'Concejal Cardozo Hector',
        'Concejal Dardo Romero' => 'Concejal Dardo Romero',
        'Concejal Dib Jair' => 'Concejal Dib Jair',
        'Concejal Gomez Valeria' => 'Concejal Gomez Valeria',
        'Concejal Jimenez Eva' => 'Concejal Jimenez Eva',
        'Concejal Koch Santiago' => 'Concejal Koch Santiago',
        'Concejal Martinez Ángel' => 'Concejal Martinez Ángel',
        'Concejal Martinez Horacio' => 'Concejal Martinez Horacio',
        'Concejal Mazal Malena' => 'Concejal Mazal Malena',
        'Concejal Salom Judith' => 'Concejal Salom Judith',
        'Concejal Scromeda Luciana' => 'Concejal Scromeda Luciana',
        'Concejal Traid Laura' => 'Concejal Traid Laura',
        'Concejal Velazquez Pablo' => 'Concejal Velazquez Pablo',
        'Concejal Vigo Daniel' => 'Concejal Vigo Daniel',
        'Concejal Zarza Fernando' => 'Concejal Zarza Fernando',
        'Concejal Horianski Santiago' => 'Concejal Horianski Santiago',
        'Concejal Fernandez María Elena' => 'Concejal Fernandez María Elena',


        // Direcciones
        'Dirección de Abastecimiento' => 'Dirección de Abastecimiento',
        'Dirección de Asuntos Jurídicos' => 'Dirección de Asuntos Jurídicos',
        'Dirección de Contabilidad y Presupuesto' => 'Dirección de Contabilidad y Presupuesto',
        'Dirección de Desarrollo Humano' => 'Dirección de Desarrollo Humano',
        'Dirección de Digesto Jurídico' => 'Dirección de Digesto Jurídico',
        'Dirección de Discapacidad' => 'Dirección de Discapacidad',
        'Dirección de Gestión y TIC' => 'Dirección de Gestión y TIC',
        'Dirección de Liquidación de Sueldos' => 'Dirección de Liquidación de Sueldos',
        'Dirección de Obras e Infraestructura' => 'Dirección de Obras e Infraestructura',
        'Dirección de Personal' => 'Dirección de Personal',
        'Dirección de Prensa' => 'Dirección de Prensa',
        'Dirección de RR.HH' => 'Dirección de RR.HH',
        'Dirección de RR.PP y Ceremonial' => 'Dirección de RR.PP y Ceremonial',
        'Dirección de Salud Mental' => 'Dirección de Salud Mental',

        // Direcciones Generales
        'Dirección General de Administración y Contabilidad' => 'Dirección General de Administración y Contabilidad',
        'Dirección General de Asuntos Legislativos y Comisiones' => 'Dirección General de Asuntos Legislativos y Comisiones',
        'Dirección General de Gestión Financiera y Administrativa' => 'Dirección General de Gestión Financiera y Administrativa',

        // Departamentos
        'Departamento de Archivos' => 'Departamento de Archivos',
        'Departamento de Asuntos Legislativos' => 'Departamento de Asuntos Legislativos',
        'Departamento de Bienes Patrimoniales' => 'Departamento de Bienes Patrimoniales',
        'Departamento de Comisiones' => 'Departamento de Comisiones',
        'Departamento de Compras y Licitaciones' => 'Departamento de Compras y Licitaciones',
        'Departamento de Cómputos' => 'Departamento de Cómputos',
        'Departamento de Mesa de Entradas y Salidas' => 'Departamento de Mesa de Entradas y Salidas',
        'Departamento de Reconocimiento Médico' => 'Departamento de Reconocimiento Médico',
        'Departamento de Sumario' => 'Departamento de Sumario',

        // Secciones
        'Sección Biblioteca' => 'Sección Biblioteca',
        'Sección Computos' => 'Sección Computos',
        'Sección Cuerpo Taquígrafos' => 'Sección Cuerpo Taquígrafos',
        'Sección Legajo y Archivo' => 'Sección Legajo y Archivo',
        'Sección Liquidación de Sueldos y Jornales' => 'Sección Liquidación de Sueldos y Jornales',
        'Sección Mantenimiento' => 'Sección Mantenimiento',
        'Sección Previsional' => 'Sección Previsional',
        'Sección Seguridad' => 'Sección Seguridad',
        'Sección Servicios Generales' => 'Sección Servicios Generales',
        'Sección Sumario' => 'Sección Sumario',
        'Sección Suministro' => 'Sección Suministro',

        // Otras áreas
        'Agenda HCD' => 'Agenda HCD',
        'Coordinación de Jurídico y Administración' => 'Coordinación de Jurídico y Administración',
        'Defensora del Pueblo' => 'Defensora del Pueblo',
        'División Cuota Alimentaria y EMB. JUD.' => 'División Cuota Alimentaria y EMB. JUD.',
        'División Presupuesto y Rendición de Cuentas' => 'División Presupuesto y Rendición de Cuentas',
        'Municipalidad de Posadas' => 'Municipalidad de Posadas',
        'Presidencia' => 'Presidencia',
        'Prosecretaria Administrativa' => 'Prosecretaria Administrativa',
        'Prosecretaria Legislativa' => 'Prosecretaria Legislativa',
        'Secretaría' => 'Secretaría'
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Updating offices to new structure');

        // Remove all existing offices
        $existingOffices = $this->entityManager->getRepository(Office::class)->findAll();
        $removed = count($existingOffices);

        foreach ($existingOffices as $office) {
            $this->entityManager->remove($office);
        }

        if ($removed > 0) {
            $this->entityManager->flush();
            $io->writeln(sprintf('  - Removed %d existing offices', $removed));
        }

        // Create new offices
        $created = 0;

        foreach ($this->newOffices as $name => $location) {
            $office = new Office();
            $office->setName($name);
            $office->setLocation($location);

            $this->entityManager->persist($office);
            $io->writeln(sprintf('  - Created office: %s', $name));
            $created++;
        }

        $this->entityManager->flush();
        $io->success(sprintf('Successfully created %d new offices', $created));

        return Command::SUCCESS;
    }
}
