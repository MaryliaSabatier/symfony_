<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\Evenement;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AbonnementController extends AbstractController
{
    #[Route('/evenement/{id}/abonner', name: 'evenement_abonner', methods: ['POST'])]
    public function abonner(
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        AbonnementRepository $abonnementRepository,
        Request $request
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous abonner à un événement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifie si l'utilisateur est déjà abonné
        $abonnementExistant = $abonnementRepository->findOneBy([
            'user' => $user,
            'evenement' => $evenement,
        ]);

        if ($abonnementExistant) {
            $this->addFlash('error', 'Vous êtes déjà abonné à cet événement.');
        } else {
            $abonnement = new Abonnement();
            $abonnement->setUser($user);
            $abonnement->setEvenement($evenement);

            $entityManager->persist($abonnement);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes maintenant abonné à cet événement.');
        }

        // Redirection vers la page précédente
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('fil_commun'));
    }

    #[Route('/evenement/{id}/desabonner', name: 'evenement_desabonner', methods: ['POST'])]
    public function desabonner(
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        AbonnementRepository $abonnementRepository,
        Request $request
    ): Response {
        $user = $this->getUser();

        // Trouver l'abonnement correspondant
        $abonnement = $abonnementRepository->findOneBy([
            'user' => $user,
            'evenement' => $evenement,
        ]);

        if (!$abonnement) {
            $this->addFlash('error', 'Vous n\'êtes pas abonné à cet événement.');
        } else {
            $entityManager->remove($abonnement);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes maintenant désabonné de cet événement.');
        }

        // Redirection vers la page précédente
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('fil_commun'));
    }
}
