<?php

namespace App\Form;

use App\Entity\Trip;
use App\Entity\Itineraire;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ItineraireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,[
                'required' => true,
                'label' => false,
                'attr' => ['name' => 'itineraire[fullName]',
                    'id' => 'itineraire_fullName',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(null, 'Nom  ne peut pas être vide.'),
                ],
            ])
            ->add('dayProgram', TextareaType::class, [
                'required' => true,
                'label' => false,
                'attr' => ['name' => 'itineraire[dayProgram]',
                'id' => 'itineraire_dayProgram',
                'class' => 'form-control',
            ],
                'constraints' => [
                    new NotBlank(null, 'dayProgram ne peut pas être vide.'),
                ],
            ])
            ->add('Activity', TextareaType::class, [
                'required' => true,
                'label' => false,
                'attr' => ['name' => 'itineraire[Activity]',
                'id' => 'itineraire_Activity',
                'class' => 'form-control',
            ],
                'constraints' => [
                    new NotBlank(null, 'Activity ne peut pas être vide.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Itineraire::class,
            'method' => 'POST',
            'attr' => [
                'id' => 'form_itineraire',
                'enctype' => 'multipart/form-data'
            ],
        ]);
    }
}
