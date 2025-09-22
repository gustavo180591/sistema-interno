<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNewTicket = $options['is_new_ticket'] ?? false;
        $user = $options['user'] ?? null;

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
            ->add('assignedUsers', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() ?: $user->getUserIdentifier();
                },
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
                'required' => false,
                'attr' => [
                    'class' => 'select2-multiple',
                    'data-placeholder' => 'Seleccione usuarios asignados',
                ],
                'label' => 'Usuarios Asignados',
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('u.nombre', 'ASC');
                },
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
        };

        $builder->add('areaOrigen', ChoiceType::class, [
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
                // Add more categories here if needed
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
        ]);

        if (!$isNewTicket) {
            $builder->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ],
                'row_attr' => [
                    'class' => 'mb-3'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'is_new_ticket' => false,
            'user' => null,
            'is_admin' => false,
        ]);

        $resolver->setAllowedTypes('user', [User::class, 'null']);
        $resolver->setAllowedTypes('is_new_ticket', 'bool');
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
