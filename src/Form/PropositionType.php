<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Proposition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropositionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'attr' => ['minlength' => 3, 'maxlength' => 100, 'placeholder' => 'Titre de la proposition'],
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['minlength' => 10, 'maxlength' => 500, 'placeholder' => 'Description détaillée', 'rows' => 5],
            ])
            ->add('date', null, [
                'widget' => 'single_text',
                'attr' => ['max' => (new \DateTime())->format('Y-m-d\TH:i'), 'readonly' => 'readonly'],
                'disabled' => true,
            ])
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nomproduit',
                'placeholder' => 'Choisir un produit',
                'required' => true,
            ])
            ->add('prixPropose', NumberType::class, [
                'required' => false,
                'label' => 'Prix proposé (TND)',
                'attr' => [
                    'placeholder' => 'Ex: 45.00 — Cliquez sur "Estimer" pour suggestion IA',
                    'step' => '0.01',
                    'min' => '0',
                ],
                'html5' => true,
            ])
            ->add('image', HiddenType::class, ['required' => false])
            ->add('clientPhone', TextType::class, [
                'required' => true,
                'label' => 'Votre numéro de téléphone',
                'attr' => ['placeholder' => '+216XXXXXXXX'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Proposition::class]);
    }
}

