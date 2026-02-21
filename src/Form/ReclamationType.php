<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', ChoiceType::class, [
                'label' => 'Type de reclamation',
                'choices' => [
                    'Reclamation technique' => 'Reclamation technique',
                    'Probleme de commande' => 'Probleme de commande',
                    'Autre' => 'Autre',
                ],
                'placeholder' => 'Selectionnez un type de reclamation',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('descripition', TextareaType::class, [
                'label' => 'Description (optionnelle)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Decrivez votre probleme en detail (min. 5 caracteres si rempli)...',
                    'rows' => 5,
                    'class' => 'form-control',
                    'minlength' => 5,
                    'maxlength' => 1000,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
