<?php

namespace App\Form;

use App\Entity\Ring;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',TextType::class, [
                'required' => true,
                'attr' => [
                    'maxlength' => 40,
                    'autocomplete' => 'off',
                    'placeholder' => 'Ring title'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Title cannot be blank.',
                    ]),
                    new Length([
                        'min' => 4,
                        'max' => 40,
                        'minMessage' => 'Title must be at least {{ limit }} characters.',
                        'maxMessage' => 'Title cannot be longer than {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Z][A-Za-z0-9 ]*$/',
                        'message' => 'Title can only contain letters and numbers.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 10,
                    'style' => 'height: 100px',
                    'maxlength' => 255,
                    'autocomplete' => 'off',
                    'placeholder' => 'Ring description',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Description cannot be blank.',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 255,
                        'minMessage' => 'Description must be at least {{ limit }} characters.',
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('banner', FileType::class, [
                'label' => 'Ring banner',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF, WEBP)',
                    ]),
                ]])
            ->add('interest',TextType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'maxlength' => 25,
                    'autocomplete' => 'off',
                    'placeholder' => 'Ring interest'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Interest cannot be blank.',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 25,
                        'minMessage' => 'Interest must be at least {{ limit }} characters.',
                        'maxMessage' => 'Interest cannot be longer than {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9]*$/',
                        'message' => 'Interest can only contain letters and numbers.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ring::class,
        ]);
    }
}
