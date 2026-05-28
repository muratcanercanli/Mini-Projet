<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 255),
                ],
                'attr' => ['placeholder' => 'ex. Renault Clio V 1.0 TCe 90'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(message: 'La description est obligatoire.'),
                    new Length(max: 255),
                ],
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'État général, kilométrage, équipements, historique…',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => '— Choisir une catégorie —',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir une catégorie.'),
                ],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Prix (€)',
                'constraints' => [
                    new NotBlank(message: 'Le prix est obligatoire.'),
                    new Positive(message: 'Le prix doit être supérieur à 0.'),
                ],
                'attr' => ['placeholder' => '15000'],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'constraints' => [
                    new NotBlank(message: 'Le stock est obligatoire.'),
                    new PositiveOrZero(message: 'Le stock ne peut pas être négatif.'),
                ],
                'attr' => ['placeholder' => '1'],
            ])
            ->add('stockMin', IntegerType::class, [
                'label' => 'Stock minimum (alerte)',
                'required' => false,
                'constraints' => [
                    new PositiveOrZero(message: 'Le stock minimum ne peut pas être négatif.'),
                ],
                'attr' => ['placeholder' => 'ex. 2'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
