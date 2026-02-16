<?php

namespace App\Form;

use App\Entity\Consommation;
use App\Entity\Logement;
use App\Entity\TypeEnergie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ConsommationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $builder
            ->add('logement', EntityType::class, [
                'class' => Logement::class,
                'label' => 'Logement',
                'choice_label' => 'adresse',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('l')
                    ->andWhere('l.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('l.adresse', 'ASC'),
                'placeholder' => 'Choisir un logement',
            ])
            ->add('typeEnergie', EntityType::class, [
                'class' => TypeEnergie::class,
                'label' => 'Type d\'énergie',
                'choice_label' => 'libelle',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('t')
                    ->andWhere('t.user = :user')
                    ->setParameter('user', $user)
                    ->orderBy('t.libelle', 'ASC'),
                'placeholder' => 'Choisir un type',
            ])
            ->add('periodeDebut', DateType::class, [
                'label' => 'Période début',
                'widget' => 'single_text',
            ])
            ->add('periodeFin', DateType::class, [
                'label' => 'Période fin',
                'widget' => 'single_text',
            ])
            ->add('valeur', NumberType::class, [
                'label' => 'Valeur',
                'html5' => true,
                'input' => 'number',
                'attr' => ['step' => '0.01', 'min' => 0],
                'constraints' => [new PositiveOrZero(message: 'La valeur ne peut pas être négative.')],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $consommation = $event->getData();
            if (!$consommation || !$consommation->getTypeEnergie()) {
                return;
            }
            $tarif = $consommation->getTypeEnergie()->getTarifUnitaire();
            if ($tarif !== null && $consommation->getValeur() !== null) {
                $consommation->setCoutEstime($consommation->getValeur() * $tarif);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consommation::class,
            'user' => null,
        ]);
        $resolver->setRequired('user');
    }
}
