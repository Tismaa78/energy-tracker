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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        ];

        $consommations = $repository->findByUser($user->getId(), array_filter($filters));
        $types = $typeRepo->findByUser($user);
        $logements = $logementRepo->findByUser($user);

        return $this->render('consommation/index.html.twig', [
            'consommations' => $consommations,
            'types' => $types,
            'logements' => $logements,
            'filters' => $filters,
        ]);
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
