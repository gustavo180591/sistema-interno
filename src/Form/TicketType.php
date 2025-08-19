<?php
// src/Form/TicketType.php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\Area;
use App\Repository\AreaRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TicketType extends AbstractType
{
    public function __construct(private AreaRepository $areaRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticketId', TextType::class, [
                'label' => 'ID del Ticket',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: ABC-123',
                    'autocomplete' => 'off',
                    'oninput' => 'this.value = this.value.toUpperCase()',
                    'autofocus' => true,
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor ingrese un ID de ticket',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'El ID del ticket debe tener al menos {{ limit }} caracteres',
                        'maxMessage' => 'El ID del ticket no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
                'help' => 'Código interno para rastrear la tarea.',
            ])
            ->add('area', EntityType::class, [
                'class' => Area::class,
                'label' => 'Área de Origen',
                'placeholder' => 'Seleccione un área',
                'required' => true,
                'query_builder' => function (AreaRepository $er) {
                    return $er->createQueryBuilder('a')
                        ->where('a.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('a.nombre', 'ASC');
                },
                'choice_label' => 'nombre',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotNull([
                        'message' => 'Por favor seleccione un área de origen',
                    ]),
                ],
            ])
            ->add('pedido', TextType::class, [
                'label' => 'Solicitud / Pedido',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Describa el pedido o solicitud'
                ],
                'help' => 'Describa con detalle lo que se está solicitando.',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor ingrese el pedido o solicitud',
                    ]),
                ]
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'class' => 'form-control',
                    'placeholder' => 'Describa detalladamente el trabajo o solicitud...',
                    'data-controller' => 'textarea-autogrow'
                ],
                'help' => 'Describa con detalle el trabajo o solicitud que se está realizando.',
            ])
            ->add('departamento', ChoiceType::class, [
                'label' => 'Área De Origen',
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
                'placeholder' => 'Seleccionar área de origen...',
                'attr' => [
                    'class' => 'form-select',
                    'data-choices' => 'true'
                ],
                'help' => 'Seleccioná el área de origen del trabajo de la Municipalidad de Posadas.',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor seleccione un departamento',
                    ]),
                ]
            ]);

        // Only add estado field when editing
        if ($options['is_edit']) {
            $builder->add('estado', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Pendiente' => 'pendiente',
                    'En proceso' => 'en proceso',
                    'Terminado' => 'terminado',
                    'Rechazado' => 'rechazado',
                ],
                'placeholder' => 'Seleccionar...',
                'attr' => [
                    'class' => 'form-select',
                    'data-choices' => 'true'
                ],
                'help' => 'Pendiente, En proceso, Terminado o Rechazado.',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor seleccione un estado',
                    ]),
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}

