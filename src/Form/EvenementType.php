<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'événement',
                'attr' => ['placeholder' => 'Entrez le nom de l\'événement'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => ['rows' => 5, 'placeholder' => 'Décrivez l\'événement'],
            ])
            ->add('typeArt', ChoiceType::class, [
                'label' => 'Type d\'art',
                'choices' => [
                    'Peinture' => 'Peinture',
                    'Sculpture' => 'Sculpture',
                    'Artisanat' => 'Artisanat',
                    'Céramique' => 'Céramique',
                    'Décoration' => 'Décoration',
                    'Mix artistique' => 'Mix artistique',
                ],
                'placeholder' => 'Choisir un type d\'art',
            ])
            ->add('theme', TextType::class, [
                'label' => 'Thème artistique',
                'attr' => ['placeholder' => 'Ex: Exposition de céramique artisanale moderne'],
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Fichier de couverture (optionnel)',
                'mapped' => false,
                'required' => false,
            ])
            ->add('additionalImages', FileType::class, [
                'label' => 'Fichiers de la galerie',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['placeholder' => 'Adresse ou lieu de l\'événement'],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Nombre de places disponibles',
                'attr' => ['min' => 1],
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'TND',
                'attr' => ['placeholder' => 'Ex: 25.00'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
