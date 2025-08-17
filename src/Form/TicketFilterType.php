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
