<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'class' => 'form-control', // Ajoute une classe CSS pour le styling
                    'placeholder' => 'Écrivez votre commentaire ici...', // Placeholder pour guider l'utilisateur
                    'rows' => 5, // Nombre de lignes dans la zone de texte
                ],
                'required' => true, // Rend ce champ obligatoire
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class, // Spécifie que ce formulaire est lié à l'entité Commentaire
        ]);
    }
}
