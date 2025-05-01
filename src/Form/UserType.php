<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Repository\CountryRepository;

class UserType extends AbstractType
{
    private CountryRepository $countryRepository; // NEW

    public function __construct(CountryRepository $countryRepository) // NEW
    {
        $this->countryRepository = $countryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = $this->countryRepository->findAll(); // Get from DB

        $countryChoices = [];
        foreach ($countries as $country) {
            $countryChoices[$country->getName()] = $country->getName(); 
            // Assume `getName()` = country_name in DB
        }

        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('gender', ChoiceType::class, [
                'choices' => ['Male' => 'Male', 'Female' => 'Female'],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('dateOfBirth', DateType::class, ['widget' => 'single_text'])
            ->add('email', EmailType::class)
            ->add('phoneNumber', TextType::class)
            ->add('country', ChoiceType::class, [
                'choices' => $countryChoices,
                'placeholder' => 'Select Country',
            ])
            ->add('profilePictureFile', VichImageType::class, [
                'required' => false,
                'label' => 'Profile Picture (JPG/PNG)',
                'allow_delete' => false,
                'download_uri' => false,
                'image_uri' => false,
                'asset_helper' => true,
            ]);          

        if ($options['include_password']) {
            $builder->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => true,
        ]);
    }
}
