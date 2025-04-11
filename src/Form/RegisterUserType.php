<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe et Confirmer le mot de passe doivent correspondre.',
                'options' => ['attr' => ['class' => 'form-control', 'minlength' => '4', 'maxlength' => '25',]],
                'required' => true,
                'first_options'  => ['label' => false, 'error_bubbling' => true],
                'second_options' => ['label' => false],
                'constraints' => [
                    new NotBlank(null, 'Le mot de passe ne peut pas être vide.'),
                    new Length([
                        'min' => 4,
                        'max' => 25,
                        'minMessage' => 'Le mot de passe doit contenir au moins 4 caractères.',
                        'maxMessage' => 'Le mot de passe ne peut pas comporter plus de 25 caractères.',
                    ]),
                ]
            ])
            ->add('fullName',TextType::class,[
                'required' => true,
                'label' => false,
                'attr' => ['name' => 'user[fullName]',
                    'id' => 'user_fullName',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(null, 'Nom & Prénom ne peut pas être vide.'),
                ],
            ])
            ->add('email',EmailType::class,[
                'required' => true,
                'label' => false,
                'attr' => ['name' => 'user[email]',
                    'id' => 'user_email',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(null, 'Email ne peut pas être vide.'),
                    new Email(['message' => 'e-mail {{ value }} n’est pas valide.
                                              Une adresse e-mail valide doit ressembler à: exemple@email.com']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'method' => 'POST',
            'attr' => [
                'id' => 'form_register',
                'enctype' => 'multipart/form-data'
            ],
        ]);
    }
}
