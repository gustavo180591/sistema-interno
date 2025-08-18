<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TicketFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Buscar por ID, pedido o descripción',
                    'class' => 'form-control',
                ],
            ])
            ->add('estado', ChoiceType::class, [
                'label' => 'Estado',
                'required' => false,
                'choices' => [
                    'Pendiente' => 'pendiente',
                    'En proceso' => 'en proceso',
                    'Terminado' => 'terminado',
                    'Rechazado' => 'rechazado',
                ],
                'placeholder' => 'Todos los estados',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('departamento', ChoiceType::class, [
                'label' => 'Área',
                'required' => false,
                'choices' => [
                    '-- Presidencia y Secretarías --' => [
                        'Presidencia' => 1,
                        'Secretaría' => 2,
                        'Prosecretaria Legislativa' => 3,
                        'Prosecretaria Administrativa' => 4,
                    ],
                    '-- Direcciones Generales --' => [
                        'Dirección General de Gestión Financiera y Administrativa' => 5,
                        'Dirección General de Administración y Contabilidad' => 6,
                        'Dirección General de Asuntos Legislativos y Comisiones' => 7,
                    ],
                    '-- Direcciones Principales --' => [
                        'Dirección de Gestión y TIC' => 8,
                        'Dirección de Desarrollo Humano' => 9,
                        'Dirección de Personal' => 10,
                        'Dirección de RR.HH' => 11,
                        'Dirección de Asuntos Jurídicos' => 12,
                        'Dirección de Contabilidad y Presupuesto' => 13,
                        'Dirección de Liquidación de Sueldos' => 14,
                        'Dirección de Abastecimiento' => 15,
                        'Dirección de Salud Mental' => 16,
                        'Dirección de Obras e Infraestructura' => 17,
                        'Dirección de RR.PP y Ceremonial' => 18,
                        'Dirección de Digesto Jurídico' => 19,
                        'Dirección de Prensa' => 20,
                    ],
                    '-- Departamentos --' => [
                        'Departamento de Archivos' => 21,
                        'Departamento de Compras y Licitaciones' => 22,
                        'Departamento de Bienes Patrimoniales' => 23,
                        'Departamento de Cómputos' => 24,
                        'Departamento de Reconocimiento Médico' => 25,
                        'Departamento de Asuntos Legislativos' => 26,
                        'Departamento de Comisiones' => 27,
                        'Departamento de Mesa de Entradas y Salidas' => 28,
                        'Departamento de Sumario' => 29,
                    ],
                    '-- Divisiones --' => [
                        'División Presupuesto y Rendición de Cuentas' => 30,
                        'División Cuota Alimentaria y EMB. JUD.' => 31,
                    ],
                    '-- Secciones --' => [
                        'Sección Computos' => 32,
                        'Sección Previsional' => 33,
                        'Sección Sumario' => 34,
                        'Sección Liquidación de Sueldos y Jornales' => 35,
                        'Sección Suministro' => 36,
                        'Sección Servicios Generales' => 37,
                        'Sección Legajo y Archivo' => 38,
                        'Sección Seguridad' => 39,
                        'Sección Mantenimiento' => 40,
                        'Sección Cuerpo Taquígrafos' => 41,
                        'Sección Biblioteca' => 42,
                    ],
                    '-- Áreas Especiales --' => [
                        'Coordinación de Jurídico y Administración' => 43,
                        'Agenda HCD' => 44,
                        'Municipalidad de Posadas' => 45,
                        'Defensora del Pueblo' => 46,
                    ],
                    '-- Concejalías --' => [
                        'Concejal Dib Jair' => 47,
                        'Concejal Velazquez Pablo' => 48,
                        'Concejal Traid Laura' => 49,
                        'Concejal Scromeda Luciana' => 50,
                        'Concejal Salom Judith' => 51,
                        'Concejal Mazal Malena' => 52,
                        'Concejal Martinez Horacio' => 53,
                        'Concejal Koch Santiago' => 54,
                        'Concejal Jimenez Eva' => 55,
                        'Concejal Gomez Valeria' => 56,
                        'Concejal Cardozo Hector' => 57,
                        'Concejal Argañaraz Pablo' => 58,
                        'Concejal Almiron Samira' => 59,
                        'Concejal Dardo Romero' => 60,
                    ],
                ],
                'placeholder' => 'Todas las áreas',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('fechaDesde', DateType::class, [
                'label' => 'Desde',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('fechaHasta', DateType::class, [
                'label' => 'Hasta',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('buscar', SubmitType::class, [
                'label' => 'Filtrar',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->add('limpiar', SubmitType::class, [
                'label' => 'Limpiar',
                'attr' => ['class' => 'btn btn-outline-secondary'],
            ]);
    }
}
