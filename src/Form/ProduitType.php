<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomproduit', ChoiceType::class, [
                'choices'  => [
                    'Plastique' => 'Plastique',
                    'Papier'    => 'Papier',
                    'Carton'    => 'Carton',
                    'Verre'     => 'Verre',
                    'Métal'     => 'Métal',
                    'Bois'      => 'Bois',
                    'Tissu'     => 'Tissu',
                ],
                'placeholder' => 'Choisir un produit',
                'required' => true,
            ])
            ->add('typemateriau', ChoiceType::class, [
                'choices'  => [
                    'Naturel'     => 'Naturel',
                    'Durable'     => 'Durable',
                    'Écologique'  => 'Écologique',
                    'Zéro déchet' => 'Zéro déchet',
                    'Réutilisable' => 'Réutilisable',
                ],
                'placeholder' => 'Choisir un type de matériau',
                'required' => true,
            ])
            ->add('etat', ChoiceType::class, [
                'choices'  => [
                    'Bon'     => 'Bon',
                    'Moyen'   => 'Moyen',
                    'Mauvais' => 'Mauvais',
                ],
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Choisir un état',
                'required' => true,
            ])
            ->add('quantite', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                ],
                'required' => true,
            ])
            ->add('origine', null, [
                'required' => true,
            ])
            ->add('impactecologique', null, [
                'required' => true,
            ])
            ->add('dateajout', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => "Date d'ajout",
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'minlength' => 10,
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
