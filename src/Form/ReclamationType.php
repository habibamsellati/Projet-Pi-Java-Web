<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

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
                    'placeholder' => 'Decrivez votre probleme en detail...',
                    'rows' => 8,
                    'class' => 'form-control',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnelle)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF, WEBP)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
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
