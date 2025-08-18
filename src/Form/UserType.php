<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Count;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'Correo electrónico *',
                'attr' => [
                    'class' => 'form-control-lg',
                    'placeholder' => 'usuario@ejemplo.com'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un correo electrónico',
                    ]),
                    new Email([
                        'message' => 'Por favor ingrese un correo electrónico válido',
                    ]),
                ],
            ])
            ->add('username', null, [
                'label' => 'Nombre de usuario *',
                'attr' => [
                    'class' => 'form-control-lg',
                    'placeholder' => 'juan.perez'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un nombre de usuario',
                    ]),
                ],
            ])
            ->add('nombre', null, [
                'label' => 'Nombre *',
                'attr' => [
                    'class' => 'form-control-lg',
                    'placeholder' => 'Juan'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un nombre',
                    ]),
                ],
            ])
            ->add('apellido', null, [
                'label' => 'Apellido *',
                'attr' => [
                    'class' => 'form-control-lg',
                    'placeholder' => 'Pérez'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un apellido',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'required' => !$options['is_edit'],
                'mapped' => false,
                'label' => 'Contraseña' . ($options['is_edit'] ? '' : ' *'),
                'attr' => [
                    'class' => 'form-control-lg',
                    'autocomplete' => 'new-password',
                    'placeholder' => '••••••'
                ],
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank([
                        'message' => 'Por favor ingrese una contraseña',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096, // max length allowed by Symfony for security reasons
                    ]),
                ],
                'help' => $options['is_edit'] ? 'Dejar en blanco para mantener la contraseña actual' : 'Mínimo 6 caracteres'
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Roles *',
                'help' => 'Seleccione al menos un rol para el usuario',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor seleccione al menos un rol',
                    ]),
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Debe seleccionar al menos un rol',
                    ]),
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'form-check-input'];
                },
                'label_attr' => ['class' => 'form-check-label me-3'],
                'row_attr' => ['class' => 'mb-3'],
                'data' => $options['is_edit'] ? $options['data']->getRoles() : ['ROLE_USER'],
                'empty_data' => ['ROLE_USER']
            ]);
        // Removed isVerified field as per user request
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}
