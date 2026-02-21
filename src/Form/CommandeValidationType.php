<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeValidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'disabled' => true,
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'minlength' => 2,
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prenom',
                'disabled' => true,
                'attr' => [
                    'placeholder' => 'Votre prenom',
                    'minlength' => 2,
                ],
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numero de telephone',
                'attr' => [
                    'placeholder' => 'Ex: 12345678 ou +21612345678',
                    'pattern' => '(\\+216|216)?[2-9][0-9]{7}',
                ],
            ])
            ->add('adresselivraison', TextType::class, [
                'label' => 'Adresse de livraison',
                'attr' => [
                    'placeholder' => 'Adresse complete',
                    'minlength' => 5,
                ],
            ])
            ->add('modepaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Especes' => 'especes',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
