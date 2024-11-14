<?php
// src/Controller/UserController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route(path: '/user/dashboard', name: 'user_dashboard')]
    public function dashboard(): Response
    {
        // Vérifie que l'utilisateur est connecté avant d'afficher le tableau de bord
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Rendu de la page de tableau de bord de l'utilisateur
        return $this->render('user/dashboard.html.twig', [
            'controller_name' => 'Tableau de bord Utilisateur',
        ]);
    }
}
