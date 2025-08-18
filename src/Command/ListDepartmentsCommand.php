<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-departments',
    description: 'Lista todos los departamentos disponibles en el sistema',
)]
class ListDepartmentsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Departamentos Disponibles - Municipalidad de Posadas');

        $departamentos = [
            '-- Presidencia y Secretarías --' => [
                1 => 'Presidencia',
                2 => 'Secretaría',
                3 => 'Prosecretaria Legislativa',
                4 => 'Prosecretaria Administrativa',
            ],
            '-- Direcciones Generales --' => [
                5 => 'Dirección General de Gestión Financiera y Administrativa',
                6 => 'Dirección General de Administración y Contabilidad',
                7 => 'Dirección General de Asuntos Legislativos y Comisiones',
            ],
            '-- Direcciones Principales --' => [
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
            ],
            '-- Departamentos --' => [
                21 => 'Departamento de Archivos',
                22 => 'Departamento de Compras y Licitaciones',
                23 => 'Departamento de Bienes Patrimoniales',
                24 => 'Departamento de Cómputos',
                25 => 'Departamento de Reconocimiento Médico',
                26 => 'Departamento de Asuntos Legislativos',
                27 => 'Departamento de Comisiones',
                28 => 'Departamento de Mesa de Entradas y Salidas',
                29 => 'Departamento de Sumario',
            ],
            '-- Divisiones --' => [
                30 => 'División Presupuesto y Rendición de Cuentas',
                31 => 'División Cuota Alimentaria y EMB. JUD.',
            ],
            '-- Secciones --' => [
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
            ],
            '-- Áreas Especiales --' => [
                43 => 'Coordinación de Jurídico y Administración',
                44 => 'Agenda HCD',
                45 => 'Municipalidad de Posadas',
                46 => 'Defensora del Pueblo',
            ],
            '-- Concejalías --' => [
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
            ],
        ];

        foreach ($departamentos as $categoria => $items) {
            $io->section($categoria);
            foreach ($items as $id => $nombre) {
                $io->text(sprintf('  %2d: %s', $id, $nombre));
            }
        }

        $io->success(sprintf('Total: %d departamentos disponibles', array_sum(array_map('count', $departamentos))));

        return Command::SUCCESS;
    }
} 