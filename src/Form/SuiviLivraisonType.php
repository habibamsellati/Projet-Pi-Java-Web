<?php

namespace App\Form;

use App\Entity\Livraison;
use App\Entity\SuiviLivraison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuiviLivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('datesuivi')
            ->add('etat')
            ->add('localisation')
            ->add('commentaire')
            ->add('livraison', EntityType::class, [
                'class' => Livraison::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SuiviLivraison::class,
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> master
