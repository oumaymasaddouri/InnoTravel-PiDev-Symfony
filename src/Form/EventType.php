<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Organizer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Event Name',
                'attr' => [
                    'placeholder' => 'Enter event name',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Enter event description',
                    'rows' => 5,
                    'class' => 'form-control'
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => [
                    'placeholder' => 'Enter event location',
                    'class' => 'form-control'
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date & Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date & Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacity',
                'attr' => [
                    'placeholder' => 'Enter maximum number of participants',
                    'min' => 1,
                    'class' => 'form-control'
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price (TND)',
                'currency' => 'TND',
                'attr' => [
                    'placeholder' => 'Enter price (0 for free events)',
                    'min' => 0,
                    'class' => 'form-control'
                ]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Cultural' => 'Cultural',
                    'Music' => 'Music',
                    'Sports' => 'Sports',
                    'Food & Drink' => 'Food & Drink',
                    'Art & Exhibition' => 'Art & Exhibition',
                    'Workshop' => 'Workshop',
                    'Conference' => 'Conference',
                    'Festival' => 'Festival',
                    'Charity' => 'Charity',
                    'Other' => 'Other'
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('organizer', EntityType::class, [
                'class' => Organizer::class,
                'choice_label' => 'name',
                'placeholder' => 'Select an organizer',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Event Image',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => true,
                    'Inactive' => false
                ],
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check-inline'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
