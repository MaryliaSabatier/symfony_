<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Repository\DiscussionRepository;
use App\Repository\EvenementRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FilCommunController extends AbstractController
{
    #[Route(path: '/fil-commun', name: 'fil_commun', methods: ['GET'])]
    public function index(
        Request $request,
        DiscussionRepository $discussionRepository,
        EvenementRepository $evenementRepository,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $query = $request->query->get('q', '');

        // Recherche pour les posts et les événements si une requête est saisie
        $posts = $query
            ? $postRepository->findBySearchQuery($query)
            : $postRepository->findAllWithComments();

        $evenements = $query
            ? $evenementRepository->findBySearchQuery($query)
            : $evenementRepository->findAll();

        $discussions = $discussionRepository->findAll();

        // Récupérer les abonnements de l'utilisateur connecté
        $user = $this->getUser();
        $abonnementIds = $user ? $entityManager->getRepository(Abonnement::class)->findSubscribedEventIdsByUser($user) : [];

        return $this->render('fil_commun/index.html.twig', [
            'discussions' => $discussions,
            'posts' => $posts,
            'evenements' => $evenements,
            'query' => $query,
            'abonnementIds' => $abonnementIds, // Transmettre les abonnements à la vue
        ]);
    }
}
