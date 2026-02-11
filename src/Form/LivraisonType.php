<?php

namespace App\Form;

<<<<<<< HEAD
use App\Entity\Commande;
use App\Entity\Livraison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
=======
use App\Entity\Livraison;
use App\Entity\Commande;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
>>>>>>> master
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
<<<<<<< HEAD
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
=======
            ->add('datelivraison', null, [
                'widget' => 'single_text',
                'label' => 'Date de livraison',
                'attr' => ['class' => 'form-control']
            ])
            ->add('addresslivraison', null, [
                'label' => 'Adresse complète',
                'attr' => ['class' => 'form-control']
            ])
            ->add('commande', EntityType::class, [
                'class' => Commande::class,
                'choice_label' => 'id', 
                'label' => 'Numéro de Commande',
                'placeholder' => '--- Choisir une commande ---',
                'required' => true,
                'attr' => ['class' => 'form-control']
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
>>>>>>> master
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livraison::class,
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> master
