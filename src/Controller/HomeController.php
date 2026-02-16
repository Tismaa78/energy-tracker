<?php

namespace App\Controller;

use App\Repository\ConsommationRepository;
use App\Repository\ObjectifRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ConsommationRepository $consommationRepository, ObjectifRepository $objectifRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->render('home/index_anonymous.html.twig');
        }

        $debutMois = new \DateTimeImmutable('first day of this month');
        $finMois = new \DateTimeImmutable('last day of this month');

        $totalMois = $consommationRepository->sommeValeurByUserAndPeriode($user->getId(), $debutMois, $finMois);
        $parType = $consommationRepository->sommeValeurByUserAndPeriodeGroupByType($user->getId(), $debutMois, $finMois);
        $objectifsRaw = $objectifRepository->findBy(['user' => $user], ['dateDebut' => 'DESC'], 10);

        // Objectif = ne pas dépasser la cible : atteint si consommation <= valeur cible, dépassé sinon
        $aujourdhui = new \DateTimeImmutable('today');
        $objectifs = [];
        $objectifsAtteints = 0;
        $objectifsDepasses = 0;
        $objectifsEnCours = 0; // période pas encore terminée (dateFin >= aujourd'hui)
        foreach ($objectifsRaw as $obj) {
            $consommationTotale = $consommationRepository->sommeValeurForObjectif($obj);
            $atteint = $consommationTotale <= $obj->getValeurCible();
            if ($atteint) {
                $objectifsAtteints++;
            } else {
                $objectifsDepasses++;
            }
            if ($obj->getDateFin() >= $aujourdhui) {
                $objectifsEnCours++;
            }
            $objectifs[] = [
                'objectif' => $obj,
                'consommation_totale' => $consommationTotale,
                'atteint' => $atteint,
            ];
        }

        return $this->render('home/index.html.twig', [
            'total_mois' => $totalMois,
            'par_type' => $parType,
            'objectifs' => $objectifs,
            'objectifs_atteints' => $objectifsAtteints,
            'objectifs_depasses' => $objectifsDepasses,
            'objectifs_en_cours' => $objectifsEnCours,
        ]);
    }
}
