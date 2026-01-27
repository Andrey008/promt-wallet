<?php

namespace App\Form;

use App\Entity\PromptTemplate;
use App\Service\TagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromptTemplateType extends AbstractType
{
    public function __construct(
        private TagService $tagService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Template Title',
                'attr' => [
                    'placeholder' => 'e.g., Code Review Request',
                    'class' => 'form-control',
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Template Body',
                'attr' => [
                    'rows' => 20,
                    'placeholder' => "Write your prompt template...\n\nAvailable placeholders:\n{{context}} - Selected contexts\n{{project.name}} - Project name\n{{project.stack}} - Tech stack\n{{date}} - Current date",
                    'class' => 'form-control font-monospace',
                ],
                'help' => 'Use {{context}}, {{project.name}}, {{project.stack}}, {{date}} placeholders',
            ])
            ->add('targetModel', ChoiceType::class, [
                'label' => 'Target AI Model',
                'choices' => array_flip(PromptTemplate::MODELS),
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'When to use this template...',
                    'class' => 'form-control',
                ],
                'help' => 'Describe when and how to use this template',
            ])
            ->add('tagsString', TextType::class, [
                'label' => 'Tags',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'e.g., review, code, debugging',
                    'class' => 'form-control',
                ],
                'help' => 'Comma-separated list of tags',
            ]);

        // Pre-populate tagsString from entity
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $template = $event->getData();
            $form = $event->getForm();

            if ($template instanceof PromptTemplate && $template->getTags()->count() > 0) {
                $tagsString = $this->tagService->tagsToString($template->getTags());
                $form->get('tagsString')->setData($tagsString);
            }
        });

        // Process tags on submit
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $template = $event->getData();
            $form = $event->getForm();

            if ($template instanceof PromptTemplate) {
                $tagsString = $form->get('tagsString')->getData();
                $template->clearTags();

                if (!empty($tagsString)) {
                    $tags = $this->tagService->parseAndCreate($tagsString);
                    foreach ($tags as $tag) {
                        $template->addTag($tag);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromptTemplate::class,
        ]);
    }
}
