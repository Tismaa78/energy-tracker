<?php

namespace App\Form;

use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('typeLogement', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Maison' => Logement::TYPE_MAISON,
                    'Appartement' => Logement::TYPE_APPART,
                ],
            ])
            ->add('surfaceM2', NumberType::class, [
                'label' => 'Surface (m²)',
                'html5' => true,
                'input' => 'number',
                'attr' => ['step' => '0.01', 'min' => 0.01],
            ])
            ->add('nbOccupants', IntegerType::class, [
                'label' => 'Nombre d\'occupants',
                'attr' => ['min' => 0],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
