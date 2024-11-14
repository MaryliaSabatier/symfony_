<?php
// src/Controller/ModerateurController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModerateurController extends AbstractController
{
    #[Route(path: '/moderateur/dashboard', name: 'moderator_dashboard')]
    public function dashboard(): Response
    {
        // Vérifie que l'utilisateur a le rôle de modérateur avant d'accéder à cette page
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        // Récupère les données nécessaires pour le tableau de bord si besoin
        // Par exemple, les discussions ou contenus nécessitant une modération

        return $this->render('moderateur/dashboard.html.twig', [
            'controller_name' => 'Tableau de bord du Modérateur',
            // Ajouter d'autres variables ici si nécessaire
        ]);
    }
}
