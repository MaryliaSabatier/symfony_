<?php
// src/Controller/FilCommunController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FilCommunController extends AbstractController
{
    #[Route(path: '/fil-commun', name: 'fil_commun')]
    public function index(): Response
    {
        return $this->render('fil_commun/index.html.twig', [
            'controller_name' => 'Fil Commun',
        ]);
    }
}
