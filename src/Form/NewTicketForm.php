<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class NewTicketForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 10,
                    'style' => 'height: 125px',
                    'maxlength' => 1000,
                    'autocomplete' => 'off',
                    'placeholder'=> 'Content here...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Content cannot be blank.',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Content must be at least {{ limit }} characters.',
                        'maxMessage' => 'Content cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'attr' => [
                    'style' => 'width: 160px',
                ],
                'choices'  => [
                    'Bug' => 'bug',
                    'Suggestion' => 'suggestion',
                    'Other' => 'other',
                ],
                'label' => 'Type',
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Choose the type...',
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
