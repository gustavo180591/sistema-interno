<?php

namespace App\Form;

use App\Entity\MaintenanceCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MaintenanceCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
            ->add('frequency', ChoiceType::class, [
                'label' => 'Frecuencia',
                'choices' => [
                    'Diaria' => 'daily',
                    'Semanal' => 'weekly',
                    'Mensual' => 'monthly',
                    'Trimestral' => 'quarterly',
                    'Semestral' => 'biannual',
                    'Anual' => 'yearly',
                    'Personalizada' => 'custom'
                ],
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('frequencyValue', null, [
                'label' => 'Valor de frecuencia',
                'required' => false,
                'help' => 'Número de días para frecuencia personalizada',
                'constraints' => [
                    new Assert\Type(['type' => 'integer']),
                    new Assert\GreaterThan(0)
                ]
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instrucciones',
                'required' => false,
                'attr' => ['rows' => 5],
                'help' => 'Instrucciones detalladas para realizar las tareas de esta categoría'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenanceCategory::class,
        ]);
    }
}
