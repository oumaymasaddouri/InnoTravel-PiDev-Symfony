<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;

class Reservation1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pickup_address', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Pickup address is required.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s]+$/',
                        'message' => 'Pickup address can only contain letters and spaces.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Enter pickup address'
                ]
            ])
            ->add('destination_address', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Destination address is required.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s]+$/',
                        'message' => 'Destination address can only contain letters and spaces.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Enter destination address'
                ]
            ])
            ->add('sepcialRequests', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter any special requests',
                    'rows' => 4
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}