<?php

namespace App\Form;

use App\Entity\Machine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MachineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inventoryNumber', TextType::class, [
                'label' => 'N° de inventario',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: INV-2023-001'
                ]
            ])
            ->add('ramGb', IntegerType::class, [
                'label' => 'RAM (GB)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 256
                ]
            ])
            ->add('cpu', TextType::class, [
                'label' => 'Procesador',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: Intel Core i5-10400'
                ]
            ])
            ->add('os', TextType::class, [
                'label' => 'Sistema Operativo',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: Windows 10 Pro'
                ]
            ])
            ->add('disk', TextType::class, [
                'label' => 'Disco',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: 500GB SSD, 1TB HDD'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Detalles adicionales sobre la máquina'
                ]
            ])
            ->add('institutional', CheckboxType::class, [
                'label' => 'Es equipo institucional',
                'required' => false,
                'label_attr' => ['class' => 'form-check-label'],
                'attr' => [
                    'class' => 'form-check-input',
                    'checked' => 'checked'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Machine::class,
        ]);
    }
}
