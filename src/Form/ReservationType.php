<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pickup_address', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Pickup address is required.']),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter pickup address',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ])
            ->add('destination_address', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Destination address is required.']),
                ],
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter destination address',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
            ])
            ->add('pickupLatitude', HiddenType::class)
            ->add('pickupLongitude', HiddenType::class)
            ->add('destinationLatitude', HiddenType::class)
            ->add('destinationLongitude', HiddenType::class)
            ->add('specialRequests', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter any special requests',
                    'rows' => 4,
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
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