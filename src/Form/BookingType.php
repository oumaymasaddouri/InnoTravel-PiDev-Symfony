<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Hotel;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startdate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Check-in Date',
                'attr' => ['class' => 'form-control']
            ])
            ->add('enddate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Check-out Date',
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Cancelled' => 'cancelled'
                ]
            ])
            ->add('userId', EntityType::class, [
                'class' => Users::class,
                'choice_label' => 'id',
            ])
            ->add('hotelId', EntityType::class, [
                'class' => Hotel::class,
                'choice_label' => 'id',
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Payment Method',
                'choices' => [
                    'Cash (Pay at Hotel)' => 'cash',
                    'Credit Card (Pay Now)' => 'stripe'
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'attr' => ['class' => 'payment-method-selector']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Complete Booking',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
