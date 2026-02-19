<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Article1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Titre de l\'article (min. 3 caracteres)',
                    'maxlength' => 255,
                    'minlength' => 3,
                ],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Contenu de l\'article (min. 10 caracteres)',
                    'minlength' => 10,
                    'rows' => 10,
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Categorie',
                'placeholder' => 'Choisir une categorie',
                'required' => false,
                'choices' => [
                    'Artisanat' => 'Artisanat',
                    'Decoration' => 'Decoration',
                    'Textile' => 'Textile',
                    'Ceramique' => 'Ceramique',
                    'Autres' => 'Autres',
                ],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (DT)',
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'min' => '0',
                    'inputmode' => 'decimal',
                    'pattern' => '\\d*(\\.\\d{1,2})?',
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
