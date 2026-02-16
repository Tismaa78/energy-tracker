<?php

namespace App\DataFixtures;

use App\Entity\Alerte;
use App\Entity\Consommation;
use App\Entity\Logement;
use App\Entity\Objectif;
use App\Entity\TypeEnergie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('demo@energy-tracker.local');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'demo1234'));
        $user->setNom('Démo');
        $user->setPrenom('Utilisateur');
        $user->setDateInscription(new \DateTimeImmutable());
        $manager->persist($user);

        $logement1 = new Logement();
        $logement1->setUser($user);
        $logement1->setAdresse('12 rue de la Paix, 75002 Paris');
        $logement1->setTypeLogement(Logement::TYPE_APPART);
        $logement1->setSurfaceM2(65.0);
        $logement1->setNbOccupants(2);
        $manager->persist($logement1);

        $logement2 = new Logement();
        $logement2->setUser($user);
        $logement2->setAdresse('5 avenue des Champs, 78000 Versailles');
        $logement2->setTypeLogement(Logement::TYPE_MAISON);
        $logement2->setSurfaceM2(120.0);
        $logement2->setNbOccupants(4);
        $manager->persist($logement2);

        $typeElec = new TypeEnergie();
        $typeElec->setUser($user);
        $typeElec->setLibelle('Electricité');
        $typeElec->setUnite('kWh');
        $typeElec->setTarifUnitaire(0.194);
        $manager->persist($typeElec);

        $typeGaz = new TypeEnergie();
        $typeGaz->setUser($user);
        $typeGaz->setLibelle('Gaz');
        $typeGaz->setUnite('kWh');
        $typeGaz->setTarifUnitaire(0.089);
        $manager->persist($typeGaz);

        $typeEau = new TypeEnergie();
        $typeEau->setUser($user);
        $typeEau->setLibelle('Eau');
        $typeEau->setUnite('m³');
        $typeEau->setTarifUnitaire(4.20);
        $manager->persist($typeEau);

        $debutMois = new \DateTime('first day of this month');
        $finMois = new \DateTime('last day of this month');

        $consommations = [];
        for ($i = 1; $i <= 10; $i++) {
            $c = new Consommation();
            $c->setUser($user);
            $c->setLogement($i % 2 === 0 ? $logement1 : $logement2);
            $types = [$typeElec, $typeElec, $typeElec, $typeGaz, $typeGaz, $typeEau, $typeEau, $typeElec, $typeGaz, $typeEau];
            $c->setTypeEnergie($types[$i - 1]);
            $jour = min($i * 2, 25);
            $pd = clone $debutMois;
            $pd->modify("+{$jour} days");
            $pf = clone $debutMois;
            $pf->modify('+' . min($jour + 5, 28) . ' days');
            $c->setPeriodeDebut($pd);
            $c->setPeriodeFin($pf);
            $c->setValeur((float) rand(5, 80));
            $c->setSourceSaisie(Consommation::SOURCE_MANUEL);
            if ($c->getTypeEnergie()->getTarifUnitaire()) {
                $c->setCoutEstime($c->getValeur() * $c->getTypeEnergie()->getTarifUnitaire());
            }
            $manager->persist($c);
            $consommations[] = $c;
        }

        $obj1 = new Objectif();
        $obj1->setUser($user);
        $obj1->setTypeEnergie($typeElec);
        $obj1->setValeurCible(150);
        $obj1->setPeriode(Objectif::PERIODE_MENSUEL);
        $obj1->setDateDebut(clone $debutMois);
        $obj1->setDateFin(clone $finMois);
        $manager->persist($obj1);

        $obj2 = new Objectif();
        $obj2->setUser($user);
        $obj2->setTypeEnergie($typeGaz);
        $obj2->setValeurCible(80);
        $obj2->setPeriode(Objectif::PERIODE_MENSUEL);
        $obj2->setDateDebut(clone $debutMois);
        $obj2->setDateFin(clone $finMois);
        $manager->persist($obj2);

        $manager->flush();

        $alerte1 = new Alerte();
        $alerte1->setTypeAlerte(Alerte::TYPE_SEUIL_DEPASSE);
        $alerte1->setMessage('Consommation électrique élevée sur la période.');
        $alerte1->setSeuilDeclenche(100);
        $alerte1->setConsommation($consommations[0]);
        $alerte1->setTypeEnergie($typeElec);
        $manager->persist($alerte1);

        $alerte2 = new Alerte();
        $alerte2->setTypeAlerte(Alerte::TYPE_SEUIL_DEPASSE);
        $alerte2->setMessage('Objectif mensuel gaz dépassé.');
        $alerte2->setSeuilDeclenche(80);
        $alerte2->setConsommation($consommations[4]);
        $alerte2->setTypeEnergie($typeGaz);
        $manager->persist($alerte2);

        $manager->flush();
    }
}
