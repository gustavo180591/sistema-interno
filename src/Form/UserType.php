<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('username')
            ->add('nombre')
            ->add('apellido')
            ->add('plainPassword', PasswordType::class, [
                'required' => !$options['is_edit'],
                'mapped' => false,
                'label' => 'Contraseña',
                'help' => $options['is_edit'] ? 'Dejar en blanco para mantener la contraseña actual' : ''
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'data' => $options['is_edit'] ? $options['data']->getRoles() : ['ROLE_USER'],
                'label' => 'Roles',
                'help' => 'Seleccione al menos un rol para el usuario',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Por favor seleccione al menos un rol',
                    ]),
                    new \Symfony\Component\Validator\Constraints\Count([
                        'min' => 1,
                        'minMessage' => 'Debe seleccionar al menos un rol',
                    ]),
                ]
            ])
            ->add('isVerified', null, [
                'label' => '¿Correo verificado?',
                'required' => false
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
