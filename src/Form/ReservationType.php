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
            ->add('dateReservation', DateTimeType::class, [
                'label' => 'Date et heure de réservation',
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut de la réservation',
                'choices'  => [
                    'En attente' => 'en_attente',
                    'Confirmé' => 'confirme',
                    'Annulé' => 'annule',
                ],
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
