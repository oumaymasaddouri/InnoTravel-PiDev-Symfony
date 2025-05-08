<?php

namespace App\Form;

use App\Entity\Transport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TransportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vehicleType', ChoiceType::class, [
                'label' => 'Vehicle Type',
                'choices' => [
                    'Car' => 'car',
                    'Taxi' => 'taxi',
                    'Minibus' => 'minibus',
                    'Truck' => 'truck',
                ],
                'placeholder' => 'Select a vehicle type',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('carModel', TextType::class, [
                'label' => 'Car Model',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Toyota Camry, Mercedes C-Class',
                ],
            ])
            ->add('carColor', TextType::class, [
                'label' => 'Car Color',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Black, White, Silver',
                ],
            ])
            ->add('licensePlate', TextType::class, [
                'label' => 'License Plate',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 123tunis4567',
                ],
                'help' => 'Format: number (max 250) + "tunis" + number (max 9999)',
            ])
            ->add('maxLuggage', ChoiceType::class, [
                'label' => 'Maximum Luggage Capacity',
                'choices' => [
                    'None' => 0,
                    '1 Suitcase' => 1,
                    '2 Suitcases' => 2,
                    '3 Suitcases' => 3,
                    '4 Suitcases' => 4,
                    '5+ Suitcases' => 5,
                ],
                'placeholder' => 'Select luggage capacity',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Vehicle Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Upload an image of the vehicle (max 2MB, JPEG/PNG/WEBP)',
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
