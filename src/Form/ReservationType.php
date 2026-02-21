<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'nom',
                'label' => 'Événement',
                'placeholder' => 'Sélectionnez un événement',
            ])
            ->add('nbPlaces', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1, 'max' => 10],
                'required' => true,
            ])
            ->add('dateReservation', DateTimeType::class, [
                'label' => 'Date et heure souhaitée',
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Modalité de réservation',
                'choices'  => [
                    'Mise en attente (Confirmation sous 3h)' => 'en_attente',
                    'Réservation ferme & Paiement immédiat' => 'confirme',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'status-radio-group']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'empty_data' => function () {
                $reservation = new Reservation();
                $reservation->setStatut('en_attente');
                return $reservation;
            },
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
