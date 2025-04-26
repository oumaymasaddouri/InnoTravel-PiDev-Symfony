<?php

namespace App\Form;

use App\Entity\Hotel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\File as FileConstraint;

class HotelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Hotel name is required']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Hotel name must be at least {{ limit }} characters',
                        'maxMessage' => 'Hotel name cannot be longer than {{ limit }} characters',
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Location is required']),
                    new Length(['min' => 2, 'max' => 100]),
                ]
            ])
            ->add('pricepernight', NumberType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Price per night is required']),
                    new Positive(['message' => 'Price must be a positive number']),
                ]
            ])
            ->add('rating', NumberType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Rating is required']),
                    new Range([
                        'min' => 0,
                        'max' => 5,
                        'notInRangeMessage' => 'Rating must be between {{ min }} and {{ max }}',
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Description is required']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Description should be at least {{ limit }} characters long',
                    ])
                ]
            ])
            ->add('ecocertification', CheckboxType::class, [
                'label' => 'Eco Certification',
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Hotel Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG or PNG image',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Hotel',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hotel::class,
        ]);
    }
}
