<?php

namespace App\Form;

use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre del Rol',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: Administrador'
                ]
            ])
            ->add('roleName', TextType::class, [
                'label' => 'Identificador del Rol',
                'help' => 'Debe comenzar con ROLE_ (ej: ROLE_ADMIN)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ej: ROLE_ADMIN'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
