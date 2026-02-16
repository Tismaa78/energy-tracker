<?php

namespace App\Form;

use App\Entity\Objectif;
use App\Entity\TypeEnergie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ObjectifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'] ?? null;
        $builder
            ->add('typeEnergie', EntityType::class, [
                'class' => TypeEnergie::class,
                'label' => 'Type d\'énergie',
                'choice_label' => 'libelle',
                'query_builder' => $user ? fn ($repo) => $repo->createQueryBuilder('t')
                    ->andWhere('t.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('t.libelle', 'ASC') : null,
                'placeholder' => 'Choisir un type',
            ])
            ->add('valeurCible', NumberType::class, [
                'label' => 'Valeur cible',
                'html5' => true,
                'input' => 'number',
                'attr' => ['step' => '0.01', 'min' => 0],
                'constraints' => [new PositiveOrZero()],
            ])
            ->add('periode', ChoiceType::class, [
                'label' => 'Période',
                'choices' => [
                    'Mensuel' => Objectif::PERIODE_MENSUEL,
                    'Hebdomadaire' => Objectif::PERIODE_HEBDO,
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date fin',
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objectif::class,
            'user' => null,
        ]);
    }
}
