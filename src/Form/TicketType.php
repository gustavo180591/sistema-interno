<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\DataTransformer\UserToIdTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    private $userRepository;
    private $transformer;

    public function __construct(UserRepository $userRepository, UserToIdTransformer $transformer)
    {
        $this->userRepository = $userRepository;
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get users for the choices
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :role1 OR u.roles LIKE :role2')
            ->setParameter('role1', '%ROLE_USER%')
            ->setParameter('role2', '%ROLE_AUDITOR%')
            ->orderBy('u.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        // Create choices array with user IDs as keys and formatted names as values
        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getNombre() . ' ' . $user->getApellido()] = (string)$user->getId();
        }
        
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
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'En Progreso' => Ticket::STATUS_IN_PROGRESS,
                    'Pendiente' => Ticket::STATUS_PENDING,
                    'Rechazado' => Ticket::STATUS_REJECTED,
                    'Completado' => Ticket::STATUS_COMPLETED,
                    'Atrasado' => Ticket::STATUS_DELAYED,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'choice_label' => function($choice, $key, $value) {
                    return $key; // Use the translated labels as display text
                },
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('assignedUsers', EntityType::class, [
                'class' => User::class,
                'label' => 'Asignar a',
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role1 OR u.roles LIKE :role2')
                        ->setParameter('role1', '%ROLE_USER%')
                        ->setParameter('role2', '%ROLE_AUDITOR%')
                        ->orderBy('u.nombre', 'ASC');
                },
                'choice_label' => function(User $user) {
                    return $user->getNombre() . ' ' . $user->getApellido();
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'data-placeholder' => 'Seleccionar usuarios',
                    'multiple' => 'multiple'
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('areaOrigen', ChoiceType::class, [
                'label' => 'Área de Origen',
                'choices' => [
                    // Concejales
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
                ],
                'placeholder' => 'Seleccione un área',
                'attr' => [
                    'class' => 'form-select',
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Observación',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Describa el problema o solicitud en detalle',
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ])
            ;

        // Add data transformer for assignedUsers field
        $builder->get('assignedUsers')->addModelTransformer(new \Symfony\Component\Form\CallbackTransformer(
            // Transform from entity to form (when editing)
            function ($assignedUsers) {
                if ($assignedUsers instanceof \Doctrine\Common\Collections\Collection) {
                    return $assignedUsers->toArray();
                }
                return $assignedUsers;
            },
            // Transform from form to entity (when submitting)
            function ($assignedUsers) {
                return $assignedUsers;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_admin' => false,
            'data_class' => Ticket::class,
        ]);
    }
}
