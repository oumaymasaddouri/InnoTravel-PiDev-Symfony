<?php

namespace App\Form;

use App\Entity\Transport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class transport1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('carModel', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter car model',
                    'data-field' => 'model'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('carColor', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter car color',
                    'data-field' => 'color'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('licensePlate', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter license plate',
                    'data-field' => 'license'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('maxLuggage', NumberType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm',
                    'placeholder' => 'Enter max luggage capacity',
                    'data-field' => 'luggage',
                    'min' => '0',
                    'max' => '10'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
            ->add('vehicleType', ChoiceType::class, [
                'choices' => [
                    'Car' => 'car',
                    'Taxi' => 'taxi',
                    'Minibus' => 'minibus',
                    'Truck' => 'truck',
                ],
                'placeholder' => 'Choose a vehicle type',
                'attr' => [
                    'class' => 'form-control form-control-lg shadow-sm select2',
                    'data-field' => 'vehicleType'
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ]
            ])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transport::class,
        ]);
    }
}