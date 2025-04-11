<?php

namespace App\Form;

use App\Entity\Trip;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('arrivalDate', DateTimeType::class,[
                'date_label' => 'Arrival Date',
                'required'   => true,
                'widget' => 'single_text',
                'attr' => ['name' => 'trip[arrivalDate]',
                    'id' => 'trip_arrivalDate',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Arrival Date ne peut pas être vide.']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Arrival Date cannot be in the past.',
                    ]),
                ],
            ])
            ->add('departure', DateTimeType::class,[
                'date_label' => 'Departure Date',
               'required'   => true,
                'widget' => 'single_text',
                'attr' => ['name' => 'trip[departure]',
                    'id' => 'Trip_departure_date',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Departure Date ne peut pas être vide.']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Departure Date cannot be in the past.',
                    ]),
                ],
            ])
            ->add('budget', MoneyType::class, [
                'label' => false,
                'currency' => 'USD', 
                'required'   => true,
                'grouping' => true, 
                'attr' => ['name' => 'trip[budget]',
                    'id' => 'Trip_budget',
                    'class' => 'form-control'],
                    'constraints' => [
                        new NotBlank(null, 'budget Date ne peut pas être vide.'),
                    ],
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getFullName();
                },
                'placeholder' => 'Choose a user',
                'required' => true,
                'attr' => ['name' => 'trip[user]',
                    'id' => 'Trip_user',
                    'class' => 'form-control'],
                'constraints' => [
                        new NotBlank(null, 'User ne peut pas être vide.'),
                    ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Accept' => 'Accept',
                    'Pending' => 'Pending',
                    'canceled' => 'canceled',
                ],
                'placeholder' => 'Choose a status',
                'empty_data' => null,
                'required' => false,
                'label' => false,
                'attr' => [
                    'name' => 'trip[status]',
                    'id' => 'trip_status',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'User ne peut pas être vide.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'method' => 'POST',
            'attr' => [
                'id' => 'form_trip',
                'enctype' => 'multipart/form-data'
            ],
        ]);
    }
}
