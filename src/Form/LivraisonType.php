<?php

namespace App\Form;

use App\Entity\Livraison;
use App\Entity\Commande;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('datelivraison', null, [
                'widget' => 'single_text',
                'label' => 'Date de livraison',
                'attr' => ['class' => 'form-control']
            ])
            ->add('addresslivraison', null, [
                'label' => 'Adresse complète',
                'attr' => ['class' => 'form-control']
            ])
            // src/Form/LivraisonType.php

->add('commande', EntityType::class, [
    'class' => Commande::class,
    'choice_label' => 'id', 
    'label' => 'Numéro de Commande',
    'placeholder' => '--- Choisir une commande ---',
    'required' => true,
    'attr' => ['class' => 'form-control'],
    'query_builder' => function (EntityRepository $er) {
        return $er->createQueryBuilder('c')
            // On fait une jointure avec la table livraison
            ->leftJoin('App\Entity\Livraison', 'l', 'WITH', 'l.commande = c')
            // On filtre : 
            // 1. Soit la commande n'a aucune livraison (l.id IS NULL)
            // 2. Soit la livraison existe mais son statut n'est pas 'livré'
            ->where('l.id IS NULL')
            ->orWhere('l.statutlivraison != :statut')
            ->setParameter('statut', 'livre') // Assure-toi que c'est le bon mot exact
            ->orderBy('c.id', 'ASC');
    },
])
            ->add('livreur', EntityType::class, [
    'class' => User::class,
    'choice_label' => 'email',
    'label' => 'Assigner à un Livreur',
    'placeholder' => '--- Sélectionner un livreur ---',
    'required' => true,
    'attr' => ['class' => 'form-control'],
    'query_builder' => function (EntityRepository $er) {
        return $er->createQueryBuilder('u')
            ->where('u.role = :val') // Utilisation de '=' car c'est une valeur exacte
            ->setParameter('val', 'livreur') // On cherche exactement le mot 'livreur'
            ->orderBy('u.email', 'ASC');
    },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
        ]);
    }
}