<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNewTicket = $options['is_new_ticket'] ?? false;

        $builder
            ->add('title', TextareaType::class, [
                'label' => 'Ticket',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el Ticket',
                    'rows' => 3,
                    'style' => 'font-size: 1.1rem; resize: vertical;'
                ],
                'row_attr' => [
                    'class' => 'mb-4'
                ]
            ])
            ->add('idSistemaInterno', TextType::class, [
                'label' => 'ID Externo',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese el ID del sistema externo'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ]);
            
        if (!$isNewTicket) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Pendiente' => Ticket::STATUS_PENDING,
                    'En progreso' => Ticket::STATUS_IN_PROGRESS,
                    'Completado' => Ticket::STATUS_COMPLETED,
                    'Rechazado' => Ticket::STATUS_REJECTED,
                    'Retrasado' => Ticket::STATUS_DELAYED,
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ],
                'required' => true
            ]);
        }
        
        // Add assignedUsers field for multiple user assignment with checkboxes
        $builder->add('assignedUsers', EntityType::class, [
            'class' => User::class,
            'choice_label' => function(User $user) {
                return $user->getNombre() . ' ' . $user->getApellido();
            },
            'query_builder' => function (UserRepository $userRepository) {
                return $userRepository->createQueryBuilder('u')
                    ->where('u.isActive = :active')
                    ->orderBy('u.nombre', 'ASC')
                    ->setParameter('active', true);
            },
            'label' => 'Asignar a',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choice_attr' => [
                'class' => 'form-check-input',
            ],
            'row_attr' => [
                'class' => 'mb-3'
            ]
        ])
        ->add('areaOrigen', ChoiceType::class, [
                'label' => 'Área de Origen',
                'choices' => [
                    'Concejales' => [
                        'Concejal Almiron Samira' => 'Concejal Almiron Samira',
                        'Concejal Argañaraz Pablo' => 'Concejal Argañaraz Pablo',
                        'Concejal Cardozo Hector' => 'Concejal Cardozo Hector',
                        'Concejal Dardo Romero' => 'Concejal Dardo Romero',
                        'Concejal Dib Jair' => 'Concejal Dib Jair',
                        'Concejal Gomez Valeria' => 'Concejal Gomez Valeria',
                        'Concejal Jimenez Eva' => 'Concejal Jimenez Eva',
                        'Concejal Koch Santiago' => 'Concejal Koch Santiago',
                        'Concejal Martinez Horacio' => 'Concejal Martinez Horacio',
                        'Concejal Mazal Malena' => 'Concejal Mazal Malena',
                        'Concejal Salom Judith' => 'Concejal Salom Judith',
                        'Concejal Scromeda Luciana' => 'Concejal Scromeda Luciana',
                        'Concejal Traid Laura' => 'Concejal Traid Laura',
                        'Concejal Velazquez Pablo' => 'Concejal Velazquez Pablo',
                    ],
                    'Direcciones' => [
                        'Coordinación de Jurídico' => 'Coordinación de Jurídico',
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
                    ],
                    'Direcciones Generales' => [
                        'Dirección General de Administración y Contabilidad' => 'Dirección General de Administración y Contabilidad',
                        'Dirección General de Asuntos Legislativos y Comisiones' => 'Dirección General de Asuntos Legislativos y Comisiones',
                        'Dirección General de Gestión Financiera y Administrativa' => 'Dirección General de Gestión Financiera y Administrativa',
                    ],
                    'Departamentos' => [
                        'Departamento de Archivos' => 'Departamento de Archivos',
                        'Departamento de Asuntos Legislativos' => 'Departamento de Asuntos Legislativos',
                        'Departamento de Bienes Patrimoniales' => 'Departamento de Bienes Patrimoniales',
                        'Departamento de Comisiones' => 'Departamento de Comisiones',
                        'Departamento de Compras y Licitaciones' => 'Departamento de Compras y Licitaciones',
                        'Departamento de Cómputos' => 'Departamento de Cómputos',
                        'Departamento de Mesa de Entradas y Salidas' => 'Departamento de Mesa de Entradas y Salidas',
                        'Departamento de Reconocimiento Médico' => 'Departamento de Reconocimiento Médico',
                        'Departamento de Sumario' => 'Departamento de Sumario',
                    ],
                    'Secciones' => [
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
                    ],
                    'Otras Áreas' => [
                        'Agenda HCD' => 'Agenda HCD',
                        'Defensora del Pueblo' => 'Defensora del Pueblo',
                        'División Cuota Alimentaria y EMB. JUD.' => 'División Cuota Alimentaria y EMB. JUD.',
                        'División Presupuesto y Rendición de Cuentas' => 'División Presupuesto y Rendición de Cuentas',
                        'Municipalidad de Posadas' => 'Municipalidad de Posadas',
                        'Presidencia' => 'Presidencia',
                        'Prosecretaria Administrativa' => 'Prosecretaria Administrativa',
                        'Prosecretaria Legislativa' => 'Prosecretaria Legislativa',
                        'Secretaría' => 'Secretaría'
                        ]
                ],
                'placeholder' => 'Seleccione un área',
                'attr' => [
                    'class' => 'form-select select2-search',
                    'data-placeholder' => 'Buscar área de origen...',
                    'data-allow-clear' => 'true',
                ],
                'required' => false,
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('assignedUsers', HiddenType::class, [
                'mapped' => false,
                'attr' => [
                    'id' => 'assigned_users_input'
                ]
            ]);

        if (!$isNewTicket) {
            $builder->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Describe el problema o solicitud con detalle...',
                    'style' => 'resize: vertical;'
                ],
                'row_attr' => [
                    'class' => 'mb-4'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
            'is_new_ticket' => false,
            'data_class' => Ticket::class,
        ]);

        $resolver->setAllowedTypes('is_new_ticket', 'bool');
    }
}
