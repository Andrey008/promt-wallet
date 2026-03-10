<?php

namespace App\Form;

use App\Entity\Snippet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SnippetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g., API Key Header',
                ],
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'data-easymde' => true,
                    'placeholder' => 'Snippet content...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Snippet::class,
        ]);
    }
}
