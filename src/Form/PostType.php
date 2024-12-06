<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Discussion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'placeholder' => 'Écrivez ici votre message...',
                    'class' => 'form-control',
                    'rows' => 5,
                ],
            ])
            ->add('discussion', EntityType::class, [
                'class' => Discussion::class,
                'choice_label' => 'nom',
                'label' => 'Associer à une discussion',
                'placeholder' => 'Choisissez une discussion (obligatoire)',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
