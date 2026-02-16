<?php

namespace App\Controller;

use App\Entity\Objectif;
use App\Form\ObjectifType;
use App\Repository\ConsommationRepository;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/objectif')]
class ObjectifController extends AbstractController
{
    #[Route('/', name: 'app_objectif_index', methods: ['GET'])]
    public function index(ObjectifRepository $repository, ConsommationRepository $consommationRepository): Response
    {
        $user = $this->getUser();
        $objectifsRaw = $repository->findBy(['user' => $user], ['dateDebut' => 'DESC']);
        $objectifs = [];
        foreach ($objectifsRaw as $obj) {
            $consommationTotale = $consommationRepository->sommeValeurForObjectif($obj);
            $objectifs[] = [
                'objectif' => $obj,
                'consommation_totale' => $consommationTotale,
                'atteint' => $consommationTotale <= $obj->getValeurCible(),
            ];
        }

        return $this->render('objectif/index.html.twig', [
            'objectifs' => $objectifs,
        ]);
    }

    #[Route('/new', name: 'app_objectif_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $objectif = new Objectif();
        $objectif->setUser($this->getUser());
        $form = $this->createForm(ObjectifType::class, $objectif, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($objectif);
            $em->flush();
            $this->addFlash('success', 'Objectif créé.');

            return $this->redirectToRoute('app_objectif_index');
        }

        return $this->render('objectif/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_objectif_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Objectif $objectif, EntityManagerInterface $em): Response
    {
        if ($objectif->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ObjectifType::class, $objectif, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Objectif mis à jour.');

            return $this->redirectToRoute('app_objectif_index');
        }

        return $this->render('objectif/edit.html.twig', [
            'objectif' => $objectif,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_objectif_delete', methods: ['POST'])]
    public function delete(Request $request, Objectif $objectif, EntityManagerInterface $em): Response
    {
        if ($objectif->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $objectif->getId(), $token)) {
            $em->remove($objectif);
            $em->flush();
            $this->addFlash('success', 'Objectif supprimé.');
        }

        return $this->redirectToRoute('app_objectif_index');
    }
}
