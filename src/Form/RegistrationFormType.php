<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre prénom.'),
                    new Length(max: 255),
                ],
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre nom.'),
                    new Length(max: 255),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer une adresse e-mail.'),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre adresse.'),
                    new Length(max: 255),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre numéro de téléphone.'),
                    new Length(max: 255),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un mot de passe.'),
                    new Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
