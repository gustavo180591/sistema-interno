<?php

namespace App\Command;

use App\Entity\Area;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-default-areas',
    description: 'Adds default areas to the system',
)]
class AddDefaultAreasCommand extends Command
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
        $io->title('Agregando áreas predeterminadas al sistema');

        $defaultAreas = [
            [
                'nombre' => 'Soporte Técnico',
                'descripcion' => 'Área encargada del soporte técnico y mantenimiento de sistemas',
                'activo' => true
            ],
            [
                'nombre' => 'Desarrollo',
                'descripcion' => 'Área de desarrollo de software y aplicaciones',
                'activo' => true
            ],
            [
                'nombre' => 'Recursos Humanos',
                'descripcion' => 'Área de gestión del personal y recursos humanos',
                'activo' => true
            ],
            [
                'nombre' => 'Contabilidad',
                'descripcion' => 'Área de contabilidad y finanzas',
                'activo' => true
            ],
            [
                'nombre' => 'Ventas',
                'descripcion' => 'Área de ventas y atención al cliente',
                'activo' => true
            ],
            [
                'nombre' => 'Marketing',
                'descripcion' => 'Área de marketing y publicidad',
                'activo' => true
            ],
            [
                'nombre' => 'Operaciones',
                'descripcion' => 'Área de operaciones y logística',
                'activo' => true
            ],
            [
                'nombre' => 'Calidad',
                'descripcion' => 'Área de control de calidad',
                'activo' => true
            ],
            [
                'nombre' => 'Administración',
                'descripcion' => 'Área administrativa general',
                'activo' => true
            ],
            [
                'nombre' => 'Gerencia',
                'descripcion' => 'Área de gerencia y dirección',
                'activo' => true
            ]
        ];

        $count = 0;
        $existingAreas = $this->entityManager->getRepository(Area::class)->findAll();
        $existingAreaNames = array_map(fn($area) => $area->getNombre(), $existingAreas);

        foreach ($defaultAreas as $areaData) {
            if (in_array($areaData['nombre'], $existingAreaNames, true)) {
                $io->note(sprintf('El área "%s" ya existe, omitiendo...', $areaData['nombre']));
                continue;
            }

            $area = new Area();
            $area->setNombre($areaData['nombre']);
            $area->setDescripcion($areaData['descripcion']);
            $area->setActivo($areaData['activo']);
            $area->setFechaCreacion(new \DateTime());

            $this->entityManager->persist($area);
            $count++;

            $io->text(sprintf('Área creada: <info>%s</info>', $areaData['nombre']));
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Se han creado %d áreas predeterminadas.', $count));
        } else {
            $io->success('Todas las áreas predeterminadas ya existen en el sistema.');
        }

        return Command::SUCCESS;
    }
}
