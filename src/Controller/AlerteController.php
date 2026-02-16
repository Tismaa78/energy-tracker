<?php

namespace App\Controller;

use App\Entity\Alerte;
use App\Repository\AlerteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alerte')]
class AlerteController extends AbstractController
{
    #[Route('/', name: 'app_alerte_index', methods: ['GET'])]
    public function index(AlerteRepository $repository, Request $request): Response
    {
        $user = $this->getUser();
        $unreadOnly = $request->query->getBoolean('non_lues');
        $alertes = $repository->findByUser($user, $unreadOnly);

        return $this->render('alerte/index.html.twig', [
            'alertes' => $alertes,
            'unread_only' => $unreadOnly,
        ]);
    }

    #[Route('/{id}/lire', name: 'app_alerte_marquer_lue', methods: ['POST'])]
    public function marquerLue(Request $request, int $id, AlerteRepository $repository, EntityManagerInterface $em): Response
    {
        $alerte = $repository->find($id);
        if (!$alerte) {
            throw $this->createNotFoundException();
        }
        if ($alerte->getConsommation()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('lue' . $id, $token)) {
            $alerte->setEstLue(true);
            $em->flush();
            $this->addFlash('success', 'Alerte marquée comme lue.');
        }

        return $this->redirectToRoute('app_alerte_index');
    }
}
