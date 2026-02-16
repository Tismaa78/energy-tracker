<?php

namespace App\Form;

use App\Entity\TypeEnergie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class TypeEnergieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr' => ['placeholder' => 'ex: Electricité'],
            ])
            ->add('unite', TextType::class, [
                'label' => 'Unité',
                'attr' => ['placeholder' => 'kWh, m³, L...'],
            ])
            ->add('tarifUnitaire', NumberType::class, [
                'label' => 'Tarif unitaire',
                'required' => false,
                'html5' => true,
                'input' => 'number',
                'attr' => ['step' => '0.0001', 'min' => 0],
                'constraints' => [new PositiveOrZero()],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TypeEnergie::class,
        ]);
    }
}
