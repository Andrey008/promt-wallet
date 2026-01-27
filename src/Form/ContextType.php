<?php

namespace App\Form;

use App\Entity\Context;
use App\Entity\Project;
use App\Service\TagService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContextType extends AbstractType
{
    public function __construct(
        private TagService $tagService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'placeholder' => 'e.g., Coding Standards',
                    'class' => 'form-control',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content (Markdown)',
                'attr' => [
                    'rows' => 15,
                    'placeholder' => 'Write your context in Markdown format...',
                    'class' => 'form-control font-monospace',
                ],
            ])
            ->add('scope', ChoiceType::class, [
                'label' => 'Scope',
                'choices' => [
                    'Global (available for all projects)' => Context::SCOPE_GLOBAL,
                    'Project-specific' => Context::SCOPE_PROJECT,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',
                'label' => 'Project',
                'required' => false,
                'placeholder' => '-- Select Project --',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Required when scope is "Project-specific"',
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Sort Order',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0',
                ],
                'help' => 'Lower numbers appear first in composition',
            ])
            ->add('tagsString', TextType::class, [
                'label' => 'Tags',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'e.g., coding, rules, best-practices',
                    'class' => 'form-control',
                ],
                'help' => 'Comma-separated list of tags',
            ]);

        // Pre-populate tagsString from entity
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $context = $event->getData();
            $form = $event->getForm();

            if ($context instanceof Context && $context->getTags()->count() > 0) {
                $tagsString = $this->tagService->tagsToString($context->getTags());
                $form->get('tagsString')->setData($tagsString);
            }
        });

        // Process tags on submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $context = $event->getData();
            $form = $event->getForm();

            if ($context instanceof Context) {
                $tagsString = $form->get('tagsString')->getData();
                $context->clearTags();

                if (!empty($tagsString)) {
                    $tags = $this->tagService->parseAndCreate($tagsString);
                    foreach ($tags as $tag) {
                        $context->addTag($tag);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Context::class,
        ]);
    }
}
