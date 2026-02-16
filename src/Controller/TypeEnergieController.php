<?php

namespace App\Controller;

use App\Entity\TypeEnergie;
use App\Form\TypeEnergieType;
use App\Repository\TypeEnergieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type_energie')]
class TypeEnergieController extends AbstractController
{
    #[Route('/', name: 'app_type_energie_index', methods: ['GET'])]
    public function index(TypeEnergieRepository $repository): Response
    {
        $user = $this->getUser();
        $types = $repository->findByUser($user);

        return $this->render('type_energie/index.html.twig', [
            'type_energies' => $types,
        ]);
    }

    #[Route('/new', name: 'app_type_energie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $typeEnergie = new TypeEnergie();
        $typeEnergie->setUser($this->getUser());
        $form = $this->createForm(TypeEnergieType::class, $typeEnergie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($typeEnergie);
            $em->flush();
            $this->addFlash('success', 'Type d\'énergie créé.');

            return $this->redirectToRoute('app_type_energie_index');
        }

        return $this->render('type_energie/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_energie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeEnergie $typeEnergie, EntityManagerInterface $em): Response
    {
        if ($typeEnergie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Ce type d\'énergie ne vous appartient pas.');
        }

        $form = $this->createForm(TypeEnergieType::class, $typeEnergie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Type d\'énergie mis à jour.');

            return $this->redirectToRoute('app_type_energie_index');
        }

        return $this->render('type_energie/edit.html.twig', [
            'type_energie' => $typeEnergie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_energie_delete', methods: ['POST'])]
    public function delete(Request $request, TypeEnergie $typeEnergie, EntityManagerInterface $em): Response
    {
        if ($typeEnergie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Ce type d\'énergie ne vous appartient pas.');
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $typeEnergie->getId(), $token)) {
            $em->remove($typeEnergie);
            $em->flush();
            $this->addFlash('success', 'Type d\'énergie supprimé.');
        }

        return $this->redirectToRoute('app_type_energie_index');
    }
}
