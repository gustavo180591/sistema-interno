<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Count;
use Doctrine\ORM\EntityManagerInterface;

class UserType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre *',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ej: Juan'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un nombre',
                    ]),
                ],
            ])
            ->add('apellido', TextType::class, [
                'label' => 'Apellido *',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ej: Pérez'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un apellido',
                    ]),
                ],
            ])
            ->add('email', TextType::class, [
                'label' => 'Correo electrónico *',
                'attr' => [
                    'class' => 'form-control form-control-lg',
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
            ->add('username', TextType::class, [
                'label' => 'Nombre de usuario *',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'juan.perez'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor ingrese un nombre de usuario',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'El nombre de usuario debe tener al menos {{ limit }} caracteres',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Nueva contraseña' : 'Contraseña *',
                'required' => !$options['is_edit'],
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'autocomplete' => 'new-password',
                    'placeholder' => '••••••',
                ],
                'constraints' => $options['is_edit'] ? [
                    new Length([
                        'min' => 6,
                        'max' => 4096,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'maxMessage' => 'La contraseña no puede tener más de {{ limit }} caracteres',
                    ]),
                ] : [
                    new NotBlank([
                        'message' => 'Por favor ingrese una contraseña',
                    ]),
                    new Length([
                        'min' => 6,
                        'max' => 4096,
                        'minMessage' => 'La contraseña debe tener al menos {{ limit }} caracteres',
                        'maxMessage' => 'La contraseña no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
                'help' => $options['is_edit'] ? 'Dejar en blanco para mantener la contraseña actual' : 'Mínimo 6 caracteres'
            ]);

        // Get all roles from the database
        $roles = $this->entityManager->getRepository(Role::class)->findBy([], ['name' => 'ASC']);
        $roleChoices = [];
        
        foreach ($roles as $role) {
            $roleChoices[sprintf('%s (%s)', $role->getName(), $role->getRoleName())] = $role->getRoleName();
        }
        
        $builder->add('roles', ChoiceType::class, [
            'choices' => $roleChoices,
            'multiple' => true,
            'expanded' => true,
            'label' => 'Roles *',
            'help' => 'Seleccione al menos un rol para el usuario',
            'constraints' => [
                new NotBlank(['message' => 'Por favor seleccione al menos un rol']),
                new Count(['min' => 1, 'minMessage' => 'Debe seleccionar al menos un rol']),
            ],
            'choice_attr' => function($choice, $key, $value) {
                return [
                    'class' => 'form-check-input',
                    'data-role-name' => $value
                ];
            },
            'label_attr' => ['class' => 'form-check-label me-3'],
            'row_attr' => ['class' => 'mb-3'],
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
