<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isPasswordRequired = $options['require_password'];
        
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['placeholder' => 'Tu nombre']
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'attr' => ['placeholder' => 'Tu apellido']
            ])
            ->add('email')
            ->add('username', TextType::class, [
                'label' => 'Nombre de usuario',
                'attr' => ['placeholder' => 'Elige un nombre de usuario'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingresa un nombre de usuario',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'El nombre de usuario debe tener al menos {{ limit }} caracteres',
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => $isPasswordRequired,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control-lg',
                    'placeholder' => '••••••'
                ],
                'label' => 'Contraseña',
                'constraints' => $isPasswordRequired ? [
                    new NotBlank([
                        'message' => 'Por favor ingresa una contraseña',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ] : [],
                'help' => $isPasswordRequired ? 'Mínimo 6 caracteres' : 'Dejar en blanco para mantener la contraseña actual',
                'help_attr' => ['class' => 'form-text text-muted small']
            ])
            ->add('roles', null, [
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);
        
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
