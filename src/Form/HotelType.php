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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Validator\Constraints as Assert;

class HotelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Hotel name is required']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Hotel name must be at least {{ limit }} characters',
                        'maxMessage' => 'Hotel name cannot be longer than {{ limit }} characters',
                    ])
                ]
            ])
            ->add('location', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Location is required']),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                ]
            ])
            ->add('pricepernight', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Price per night is required']),
                    new Assert\Positive(['message' => 'Price must be a positive number']),
                ]
            ])
            ->add('rating', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Rating is required']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 5,
                        'notInRangeMessage' => 'Rating must be between {{ min }} and {{ max }}',
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Description is required']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Description should be at least {{ limit }} characters long',
                    ])
                ]
            ])
            ->add('ecocertification', CheckboxType::class, [
                'label' => 'Eco Certification',
                'required' => false,
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
