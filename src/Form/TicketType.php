<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
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
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese un título descriptivo',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Describa el problema o solicitud en detalle',
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Prioridad',
                'choices' => [
                    'Baja' => Ticket::PRIORITY_LOW,
                    'Media' => Ticket::PRIORITY_MEDIUM,
                    'Alta' => Ticket::PRIORITY_HIGH,
                    'Crítica' => Ticket::PRIORITY_CRITICAL,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);

        // Only show assignee field to admins
        if ($options['is_admin']) {
            $builder->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'label' => 'Asignar a',
                'placeholder' => 'Seleccione un usuario',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
                'choice_label' => function (User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'is_admin' => false,
        ]);
    }
}
