<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du post'
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu'
            ])
            // Remplacement de l'ancien champ imageUrls par un champ FileType
            ->add('imageFile', FileType::class, [
                'label' => 'Choisir une image',
                'mapped' => false, // Ce champ n'est pas lié directement à la propriété imageUrls de l'entité
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse e-mail',
                'required' => false,
            ])
            
            ->add('num', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
            ])
            

            
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName();
                },
                'label' => 'Utilisateur'
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
