<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $commonTasks = [
            'Seleccionar una tarea común...' => '',
            'Instalación de software' => 'Instalación de software',
            'Configuración de correo electrónico' => 'Configuración de correo electrónico',
            'Resolución de problemas de red' => 'Resolución de problemas de red',
            'Mantenimiento preventivo' => 'Mantenimiento preventivo',
            'Actualización de sistema' => 'Actualización de sistema',
            'Configuración de impresora' => 'Configuración de impresora',
            'Recuperación de datos' => 'Recuperación de datos',
            'Otro...' => 'other'
        ];

        $builder
            ->add('taskType', ChoiceType::class, [
                'label' => false,
                'choices' => $commonTasks,
                'required' => false,
                'attr' => [
                    'class' => 'form-select mb-2',
                    'id' => 'task_type_select'
                ],
                'mapped' => false
            ])
            ->add('description', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Escribe aquí la tarea personalizada...',
                    'autocomplete' => 'off',
                    'id' => 'task_description'
                ]
            ]);

        // Add a hidden field to identify if the form was submitted
        $builder->add('isSubmitted', HiddenType::class, [
            'mapped' => false,
            'data' => '1'
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            
            // If a common task was selected, use that as the description
            if (!empty($data['taskType']) && $data['taskType'] !== 'other') {
                $data['description'] = $data['taskType'];
            }
            
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'task_item',
        ]);
    }
}
