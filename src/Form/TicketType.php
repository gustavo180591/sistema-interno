<?php
// src/Form/TicketType.php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ticketId', TextType::class, [
                'label' => 'ID de Ticket',
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
            ->add('departamento', ChoiceType::class, [
                'label' => 'Departamento',
                'choices' => array_combine(
                    array_map(fn($i) => 'Departamento ' . $i, range(1, 10)),
                    range(1, 10)
                ),
                'placeholder' => 'Seleccionar...',
                'attr' => ['class' => 'form-select'],
                'help' => 'Seleccioná el destino del trabajo.',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor seleccione un departamento',
                    ]),
                ],
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción de la tarea',
                'attr' => [
                    'rows' => 6,
                    'class' => 'form-control',
                    'placeholder' => 'Contanos qué vas a hacer, alcance, insumos, links, etc.',
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor ingrese una descripción',
                    ]),
                ],
                'help' => 'Sea lo más detallado posible para una mejor atención',
            ])
            ->add('estado', ChoiceType::class, [
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
            ])
            ->add('estado', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Pendiente' => 'pendiente',
                    'En proceso' => 'en proceso',
                    'Terminado'  => 'terminado',
                    'Rechazado'  => 'rechazado',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Estado actual del ticket',
            ]);
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

