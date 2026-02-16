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
        $aujourdhui = new \DateTimeImmutable('today');

        $totalMois = $consommationRepository->sommeValeurByUserAndPeriode($user->getId(), $debutMois, $finMois);
        $coutTotalMois = $consommationRepository->sommeCoutByUserAndPeriode($user->getId(), $debutMois, $finMois);
        $parType = $consommationRepository->sommeValeurAndCoutByUserAndPeriodeGroupByType($user->getId(), $debutMois, $finMois);
        $topLogements = $consommationRepository->topLogementsByUserAndPeriode($user, $debutMois, $finMois, 3);
        $evolution30j = $consommationRepository->evolutionLast30DaysByUser($user->getId());
        $chart_conso_type_labels = array_column($parType, 'type_energie');
        $chart_conso_type_values = array_column($parType, 'total');

        $objectifsRaw = $objectifRepository->findBy(['user' => $user], ['dateDebut' => 'DESC'], 10);
        $objectifs = [];
        $objectifsAtteints = 0;
        $objectifsDepasses = 0;
        $objectifsEnCours = 0;
        $objectifProcheDepasse = null; // objectif en cours (pas dépassé) avec la marge la plus faible

        foreach ($objectifsRaw as $obj) {
            $consommationTotale = $consommationRepository->sommeValeurForObjectif($obj);
            $atteint = $consommationTotale <= $obj->getValeurCible();
            $periodeTerminee = $obj->getDateFin() < $aujourdhui;

            // Chaque objectif dans une seule catégorie : Dépassé | Atteint (période terminée) | En cours (période non terminée, pas dépassé)
            if (!$atteint) {
                $objectifsDepasses++;
            } elseif ($periodeTerminee) {
                $objectifsAtteints++;
            } else {
                $objectifsEnCours++;
                // Candidat "objectif le plus proche d'être dépassé"
                if ($obj->getValeurCible() > 0) {
                    $marge = $obj->getValeurCible() - $consommationTotale;
                    if ($objectifProcheDepasse === null || $marge < $objectifProcheDepasse['marge']) {
                        $objectifProcheDepasse = [
                            'objectif' => $obj,
                            'consommation_totale' => $consommationTotale,
                            'marge' => $marge,
                        ];
                    }
                }
            }

            $objectifs[] = [
                'objectif' => $obj,
                'consommation_totale' => $consommationTotale,
                'atteint' => $atteint,
            ];
        }

        return $this->render('home/index.html.twig', [
            'total_mois' => $totalMois,
            'cout_total_mois' => $coutTotalMois,
            'par_type' => $parType,
            'objectifs' => $objectifs,
            'objectifs_atteints' => $objectifsAtteints,
            'objectifs_depasses' => $objectifsDepasses,
            'objectifs_en_cours' => $objectifsEnCours,
            'objectif_proche_depasse' => $objectifProcheDepasse,
            'top_logements' => $topLogements,
            'chart_conso_type_labels' => $chart_conso_type_labels,
            'chart_conso_type_values' => $chart_conso_type_values,
            'chart_evolution_labels' => $evolution30j['labels'],
            'chart_evolution_values' => $evolution30j['values'],
        ]);
    }
}
