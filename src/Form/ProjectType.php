<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Project Name',
                'attr' => [
                    'placeholder' => 'e.g., My Awesome Project',
                    'class' => 'form-control',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Brief description of the project...',
                    'class' => 'form-control',
                ],
            ])
            ->add('stackString', TextType::class, [
                'label' => 'Tech Stack',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'e.g., PHP, Symfony, PostgreSQL',
                    'class' => 'form-control',
                ],
                'help' => 'Comma-separated list of technologies',
            ]);

        // Pre-populate stackString from entity
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $project = $event->getData();
            $form = $event->getForm();

            if ($project instanceof Project && $project->getStack()) {
                $form->get('stackString')->setData($project->getStackAsString());
            }
        });

        // Convert stackString to array on submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $project = $event->getData();
            $form = $event->getForm();

            if ($project instanceof Project) {
                $stackString = $form->get('stackString')->getData();
                $project->setStackFromString($stackString);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
