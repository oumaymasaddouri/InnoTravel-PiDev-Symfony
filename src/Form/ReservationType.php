<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Transport;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reservation = $options['data'];
        $preSelectedTransport = $reservation->getTransport();

        // If a transport is pre-selected, only show that transport in the dropdown
        if ($preSelectedTransport && $preSelectedTransport->getId()) {
            $builder->add('transport', EntityType::class, [
                'class' => Transport::class,
                'choice_label' => function (Transport $transport) {
                    return sprintf('%s - %s (%s)',
                        $transport->getVehicleType(),
                        $transport->getCarModel(),
                        $transport->getCarColor()
                    );
                },
                'query_builder' => function ($repository) use ($preSelectedTransport) {
                    return $repository->createQueryBuilder('t')
                        ->where('t.id = :id')
                        ->setParameter('id', $preSelectedTransport->getId());
                },
                'attr' => [
                    'class' => 'form-control',
                    'disabled' => 'disabled', // Make it visually disabled
                ],
                'label' => 'Selected Vehicle',
                'required' => true,
                'data' => $preSelectedTransport,
            ]);

            // Add a hidden field to ensure the transport is submitted even if the dropdown is disabled
            $builder->add('transport_id', HiddenType::class, [
                'mapped' => false,
                'data' => $preSelectedTransport->getId(),
            ]);
        } else {
            $builder->add('transport', EntityType::class, [
                'class' => Transport::class,
                'choice_label' => function (Transport $transport) {
                    return sprintf('%s - %s (%s)',
                        $transport->getVehicleType(),
                        $transport->getCarModel(),
                        $transport->getCarColor()
                    );
                },
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('t')
                        ->where('t.status = :status')
                        ->setParameter('status', 'Active')
                        ->orderBy('t.id', 'DESC');
                },
                'placeholder' => 'Select a vehicle',
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Vehicle',
                'required' => true,
            ]);
        }

        $builder
            ->add('pickup_address', TextType::class, [
                'label' => 'Pickup Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter pickup address',
                    'id' => 'pickup-address',
                ],
            ])
            ->add('destination_address', TextType::class, [
                'label' => 'Destination Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter destination address',
                    'id' => 'destination-address',
                ],
            ])
            ->add('pickupLatitude', HiddenType::class, [
                'attr' => ['id' => 'pickup-latitude'],
            ])
            ->add('pickupLongitude', HiddenType::class, [
                'attr' => ['id' => 'pickup-longitude'],
            ])
            ->add('destinationLatitude', HiddenType::class, [
                'attr' => ['id' => 'destination-latitude'],
            ])
            ->add('destinationLongitude', HiddenType::class, [
                'attr' => ['id' => 'destination-longitude'],
            ])
            ->add('reservationDate', DateType::class, [
                'label' => 'Reservation Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
                'html5' => true,
            ])
            ->add('reservationTime', TimeType::class, [
                'label' => 'Reservation Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'html5' => true,
            ])
            ->add('specialRequests', TextareaType::class, [
                'label' => 'Special Requests',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Any special requests or notes for the driver',
                    'rows' => 3,
                ],
            ])
        ;

        if ($options['is_admin']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'label' => 'Status',
                    'choices' => [
                        'Pending' => 'pending',
                        'Confirmed' => 'confirmed',
                        'Canceled' => 'canceled',
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('price', TextType::class, [
                    'label' => 'Price',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Enter price',
                    ],
                ])
                ->add('estimatedDuration', TextType::class, [
                    'label' => 'Estimated Duration (minutes)',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Enter estimated duration in minutes',
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'is_admin' => false,
        ]);
    }
}
