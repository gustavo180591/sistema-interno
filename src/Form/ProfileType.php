<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'disabled' => true, // Email shouldn't be changed
            ])
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
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Nueva contraseña (dejar en blanco para no cambiar)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
