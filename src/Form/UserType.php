<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nombre de usuario',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Roles',
                'choices' => [
                    'Administrador' => 'ROLE_ADMIN',
                    'Auditor' => 'ROLE_AUDITOR',
                    'Usuario' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'attr' => ['class' => 'form-check'],
            ]);

        // Only require password for new users or when explicitly changing it
        $builder->add('plainPassword', PasswordType::class, [
            'label' => $options['is_edit'] ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña',
            'mapped' => false,
            'required' => !$options['is_edit'],
            'constraints' => $options['is_edit'] ? [] : [
                new NotBlank([
                    'message' => 'Por favor ingrese una contraseña',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
                ]),
            ],
            'attr' => ['class' => 'form-control'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
