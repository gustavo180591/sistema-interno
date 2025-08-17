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
                    'placeholder' => 'Ingrese el número de ticket',
                    'data-ticket-id' => 'true',
                    'autocomplete' => 'off',
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
                'help' => 'Ingrese un identificador único para el ticket',
            ])
            ->add('descripcion', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Describa el problema o consulta',
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor ingrese una descripción',
                    ]),
                ],
                'help' => 'Sea lo más detallado posible para una mejor atención',
            ])
            ->add('departamento', ChoiceType::class, [
                'label' => 'Departamento',
                'placeholder' => 'Seleccioná un departamento',
                'choices' => [
                    'Departamento 1' => 1, 'Departamento 2' => 2, 'Departamento 3' => 3,
                    'Departamento 4' => 4, 'Departamento 5' => 5, 'Departamento 6' => 6,
                    'Departamento 7' => 7, 'Departamento 8' => 8, 'Departamento 9' => 9,
                    'Departamento 10' => 10,
                ],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor seleccione un departamento',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-select',
                    'data-choices' => 'true',
                ],
                'help' => 'Seleccione el departamento responsable',
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
