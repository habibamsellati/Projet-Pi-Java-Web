<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Livraison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // DATE SANS HEURE
            ->add('datelivraison', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de livraison',
            ])

            // ADRESSE
            ->add('addresslivraison')

            // STATUT AVEC 3 CHOIX
            ->add('statutlivraison', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Annulée'   => 'annulee',
                    'En attente'=> 'en_attente',
                    'Livrée'    => 'livree',
                ],
                'placeholder' => 'Choisir un statut',
            ])
;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
        ]);
    }
}
