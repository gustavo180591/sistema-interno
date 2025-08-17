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
                'label' => 'Nuestra respuesta / Comentarios',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese aquí nuestra respuesta o comentarios sobre el pedido...',
                    'data-controller' => 'textarea-autogrow'
                ],
                'help' => 'Ingrese aquí la respuesta o comentarios sobre el pedido realizado.',
            ])
            ->add('departamento', ChoiceType::class, [
                'label' => 'Área De Origen',
                'choices' => [
                    'Sistemas' => 1,
                    'Administración' => 2,
                    'Recursos Humanos' => 3,
                    'Contabilidad' => 4,
                    'Ventas' => 5,
                    'Atención al Cliente' => 6,
                    'Logística' => 7,
                    'Almacén' => 8,
                    'Compras' => 9,
                    'Dirección' => 10
                ],
                'placeholder' => 'Seleccionar...',
                'attr' => ['class' => 'form-select'],
                'help' => 'Seleccioná el área de origen del trabajo.',
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

