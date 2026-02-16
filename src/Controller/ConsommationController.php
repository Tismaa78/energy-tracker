<?php

namespace App\Controller;

use App\Entity\Alerte;
use App\Entity\Consommation;
use App\Form\ConsommationType;
use App\Repository\ConsommationRepository;
use App\Repository\LogementRepository;
use App\Repository\ObjectifRepository;
use App\Repository\TypeEnergieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/consommation')]
class ConsommationController extends AbstractController
{
    #[Route('/', name: 'app_consommation_index', methods: ['GET'])]
    public function index(Request $request, ConsommationRepository $repository, TypeEnergieRepository $typeRepo, LogementRepository $logementRepo): Response
    {
        $user = $this->getUser();
        $filters = [
            'type_energie_id' => $request->query->getInt('type_energie'),
            'logement_id' => $request->query->getInt('logement'),
            'periode_debut' => $request->query->get('periode_debut') ? new \DateTimeImmutable($request->query->get('periode_debut')) : null,
            'periode_fin' => $request->query->get('periode_fin') ? new \DateTimeImmutable($request->query->get('periode_fin')) : null,
            'valeur_min' => $request->query->get('valeur_min'),
            'valeur_max' => $request->query->get('valeur_max'),
        ];

        $page = max(1, $request->query->getInt('page', 1));
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'DESC');
        $perPage = 15;
        $filtered = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        $result = $repository->findByUserPaginated($user->getId(), $filtered, $page, $perPage, $sortBy, $sortOrder);

        $types = $typeRepo->findByUser($user);
        $logements = $logementRepo->findByUser($user);

        return $this->render('consommation/index.html.twig', [
            'consommations' => $result['items'],
            'total_consommations' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'types' => $types,
            'logements' => $logements,
            'filters' => $filters,
        ]);
    }

    #[Route('/export', name: 'app_consommation_export', methods: ['GET'])]
    public function export(Request $request, ConsommationRepository $repository): Response
    {
        $user = $this->getUser();
        $filters = [
            'type_energie_id' => $request->query->getInt('type_energie'),
            'logement_id' => $request->query->getInt('logement'),
            'periode_debut' => $request->query->get('periode_debut') ? new \DateTimeImmutable($request->query->get('periode_debut')) : null,
            'periode_fin' => $request->query->get('periode_fin') ? new \DateTimeImmutable($request->query->get('periode_fin')) : null,
            'valeur_min' => $request->query->get('valeur_min'),
            'valeur_max' => $request->query->get('valeur_max'),
        ];
        $filtered = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        $consommations = $repository->findByUser($user->getId(), $filtered);

        $format = $request->query->get('format', 'csv');
        if ($format === 'csv') {
            return $this->exportCsv($consommations);
        }
        if ($format === 'pdf') {
            return $this->exportPdf($consommations);
        }

        $this->addFlash('warning', 'Format d\'export inconnu.');
        return $this->redirectToRoute('app_consommation_index', $request->query->all());
    }

    private function exportCsv(array $consommations): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($consommations) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Période début', 'Période fin', 'Logement', 'Type', 'Unité', 'Valeur', 'Coût estimé (€)'], ';');
            foreach ($consommations as $c) {
                fputcsv($out, [
                    $c->getPeriodeDebut() ? $c->getPeriodeDebut()->format('Y-m-d') : '',
                    $c->getPeriodeFin() ? $c->getPeriodeFin()->format('Y-m-d') : '',
                    $c->getLogement() ? $c->getLogement()->getAdresse() : '',
                    $c->getTypeEnergie() ? $c->getTypeEnergie()->getLibelle() : '',
                    $c->getTypeEnergie() ? $c->getTypeEnergie()->getUnite() : '',
                    $c->getValeur(),
                    $c->getCoutEstime(),
                ], ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="consommations_' . date('Y-m-d_His') . '.csv"');
        return $response;
    }

    private function exportPdf(array $consommations): Response
    {
        $html = $this->renderView('consommation/export_pdf.html.twig', ['consommations' => $consommations]);
        // Sans dompdf : retourner du HTML pour impression navigateur "Enregistrer en PDF"
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/new', name: 'app_consommation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ObjectifRepository $objectifRepo): Response
    {
        $user = $this->getUser();
        $consommation = new Consommation();
        $consommation->setUser($user);
        $form = $this->createForm(ConsommationType::class, $consommation, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->calculerCoutEstime($consommation);
            $em->persist($consommation);
            $em->flush();

            $this->creerAlerteSiSeuilDepasse($consommation, $objectifRepo, $em);

            $this->addFlash('success', 'Consommation enregistrée.');

            return $this->redirectToRoute('app_consommation_index');
        }

        return $this->render('consommation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/import', name: 'app_consommation_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        EntityManagerInterface $em,
        LogementRepository $logementRepo,
        TypeEnergieRepository $typeRepo,
        ObjectifRepository $objectifRepo,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('csv_file');
            if (!$file) {
                $this->addFlash('danger', 'Aucun fichier envoyé.');
                return $this->redirectToRoute('app_consommation_import');
            }

            if (!in_array($file->getClientOriginalExtension(), ['csv', 'txt'], true)) {
                $this->addFlash('danger', 'Le fichier doit être un CSV (.csv).');
                return $this->redirectToRoute('app_consommation_import');
            }

            $handle = fopen($file->getPathname(), 'r');
            if (!$handle) {
                $this->addFlash('danger', 'Impossible de lire le fichier envoyé.');
                return $this->redirectToRoute('app_consommation_import');
            }

            $delimiter = ';';
            $row = 0;
            $imported = 0;
            $errors = [];

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row++;
                // On considère que la 1ère ligne est un header
                if ($row === 1 && isset($data[0]) && stripos($data[0], 'logement') !== false) {
                    continue;
                }
                if (count($data) < 5) {
                    $errors[] = "Ligne {$row} : colonnes manquantes (attendu : logement;type_energie;periode_debut;periode_fin;valeur).";
                    continue;
                }

                [$logementLabel, $typeLabel, $dateDebutStr, $dateFinStr, $valeurStr] = array_map('trim', $data);

                if ($logementLabel === '' || $typeLabel === '' || $dateDebutStr === '' || $dateFinStr === '' || $valeurStr === '') {
                    $errors[] = "Ligne {$row} : certaines valeurs sont vides.";
                    continue;
                }

                try {
                    $dateDebut = new \DateTimeImmutable($dateDebutStr);
                    $dateFin = new \DateTimeImmutable($dateFinStr);
                } catch (\Exception) {
                    $errors[] = "Ligne {$row} : dates invalides (format attendu : AAAA-MM-JJ).";
                    continue;
                }

                if ($dateFin < $dateDebut) {
                    $errors[] = "Ligne {$row} : la date de fin est avant la date de début.";
                    continue;
                }

                $valeur = str_replace(',', '.', $valeurStr);
                if (!is_numeric($valeur) || (float) $valeur < 0) {
                    $errors[] = "Ligne {$row} : valeur numérique invalide.";
                    continue;
                }

                $logement = $logementRepo->findOneBy(['user' => $user, 'adresse' => $logementLabel]);
                if (!$logement) {
                    $errors[] = "Ligne {$row} : logement \"{$logementLabel}\" introuvable pour votre compte.";
                    continue;
                }

                $typeEnergie = $typeRepo->findOneBy(['user' => $user, 'libelle' => $typeLabel]);
                if (!$typeEnergie) {
                    $errors[] = "Ligne {$row} : type d'énergie \"{$typeLabel}\" introuvable pour votre compte.";
                    continue;
                }

                $consommation = new Consommation();
                $consommation->setUser($user);
                $consommation->setLogement($logement);
                $consommation->setTypeEnergie($typeEnergie);
                $consommation->setPeriodeDebut($dateDebut);
                $consommation->setPeriodeFin($dateFin);
                $consommation->setValeur((float) $valeur);
                $consommation->setSourceSaisie(Consommation::SOURCE_IMPORT);

                $this->calculerCoutEstime($consommation);
                $em->persist($consommation);
                $em->flush();

                $this->creerAlerteSiSeuilDepasse($consommation, $objectifRepo, $em);

                $imported++;
            }

            fclose($handle);

            if ($imported > 0) {
                $this->addFlash('success', "{$imported} consommation(s) importée(s) avec succès.");
            }
            if (!empty($errors)) {
                $this->addFlash('warning', "Certaines lignes n'ont pas pu être importées : " . implode(' | ', array_slice($errors, 0, 5)));
            }

            return $this->redirectToRoute('app_consommation_index');
        }

        return $this->render('consommation/import.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_consommation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consommation $consommation, EntityManagerInterface $em, ObjectifRepository $objectifRepo): Response
    {
        if ($consommation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConsommationType::class, $consommation, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->calculerCoutEstime($consommation);
            $em->flush();
            $this->addFlash('success', 'Consommation mise à jour.');
            return $this->redirectToRoute('app_consommation_index');
        }

        return $this->render('consommation/edit.html.twig', [
            'consommation' => $consommation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_consommation_delete', methods: ['POST'])]
    public function delete(Request $request, Consommation $consommation, EntityManagerInterface $em): Response
    {
        if ($consommation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $consommation->getId(), $token)) {
            $em->remove($consommation);
            $em->flush();
            $this->addFlash('success', 'Consommation supprimée.');
        }

        return $this->redirectToRoute('app_consommation_index');
    }

    private function calculerCoutEstime(Consommation $consommation): void
    {
        $type = $consommation->getTypeEnergie();
        if ($type && $type->getTarifUnitaire() !== null && $consommation->getValeur() !== null) {
            $consommation->setCoutEstime($consommation->getValeur() * $type->getTarifUnitaire());
        }
    }

    private function creerAlerteSiSeuilDepasse(Consommation $consommation, ObjectifRepository $objectifRepo, EntityManagerInterface $em): void
    {
        $typeEnergie = $consommation->getTypeEnergie();
        $periodeDebut = $consommation->getPeriodeDebut();
        $periodeFin = $consommation->getPeriodeFin();
        if (!$typeEnergie || !$periodeDebut || !$periodeFin) {
            return;
        }

        $objectifs = $objectifRepo->findActiveByUserAndTypeAndPeriod(
            $consommation->getUser(),
            $typeEnergie->getId(),
            $periodeDebut,
            $periodeFin
        );

        foreach ($objectifs as $objectif) {
            if ($consommation->getValeur() > $objectif->getValeurCible()) {
                $alerte = new Alerte();
                $alerte->setTypeAlerte(Alerte::TYPE_SEUIL_DEPASSE);
                $alerte->setMessage(sprintf(
                    'Consommation (%.2f %s) dépasse l\'objectif (%.2f %s) pour la période.',
                    $consommation->getValeur(),
                    $typeEnergie->getUnite(),
                    $objectif->getValeurCible(),
                    $typeEnergie->getUnite()
                ));
                $alerte->setSeuilDeclenche($objectif->getValeurCible());
                $alerte->setConsommation($consommation);
                $alerte->setTypeEnergie($typeEnergie);
                $em->persist($alerte);
            }
        }
        $em->flush();
    }
}
