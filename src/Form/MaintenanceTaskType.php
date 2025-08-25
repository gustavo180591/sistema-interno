<?php

namespace App\Form;

use App\Entity\MaintenanceTask;
use App\Entity\User;
use App\Repository\MaintenanceCategoryRepository;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MaintenanceTaskType extends AbstractType
{
    private $categoryRepository;
    private $userRepository;

    public function __construct(MaintenanceCategoryRepository $categoryRepository, UserRepository $userRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('category', null, [
                'label' => 'Categoría',
                'placeholder' => 'Seleccione una categoría',
                'choices' => $this->categoryRepository->findAll(),
                'choice_label' => 'name',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => array_flip(MaintenanceTask::getStatuses()),
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->add('scheduledDate', DateTimeType::class, [
                'label' => 'Fecha programada',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'js-datetime-picker'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual('today')
                ]
            ])
            ->add('assignedTo', null, [
                'label' => 'Asignado a',
                'placeholder' => 'Seleccione un usuario',
                'choices' => $this->userRepository->findAll(),
                'choice_label' => 'username',
                'required' => false
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notas',
                'required' => false,
                'attr' => ['rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenanceTask::class,
        ]);
    }
}
