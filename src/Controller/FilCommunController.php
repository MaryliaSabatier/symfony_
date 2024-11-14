<?php
// src/Controller/FilCommunController.php
namespace App\Controller;

use App\Repository\DiscussionRepository;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FilCommunController extends AbstractController
{
    #[Route(path: '/fil-commun', name: 'fil_commun')]
    public function index(DiscussionRepository $discussionRepository, EvenementRepository $evenementRepository): Response
    {
        // Récupère toutes les discussions et événements
        $discussions = $discussionRepository->findAll();
        $evenements = $evenementRepository->findAll();

        return $this->render('fil_commun/index.html.twig', [
            'discussions' => $discussions,
            'evenements' => $evenements,
        ]);
    }
}

