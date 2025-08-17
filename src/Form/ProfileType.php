<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Correo electrónico',
                'constraints' => [
                    new NotBlank(['message' => 'Por favor ingresa un correo electrónico']),
                    new Email(['message' => 'Por favor ingresa un correo electrónico válido']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'tucorreo@ejemplo.com'
                ]
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Tu nombre'
                ]
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Tu apellido'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nueva contraseña (dejar en blanco para no cambiar)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'placeholder' => '••••••'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
