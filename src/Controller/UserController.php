<?php
// src/Controller/UserController.php
namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route(path: '/user/dashboard', name: 'user_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('user/dashboard.html.twig', [
            'user' => $this->getUser(), // Passer l'utilisateur au template
            'controller_name' => 'Tableau de bord Utilisateur',
        ]);
    }

    #[Route(path: '/user/edit-profile', name: 'user_edit_profile')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Créer le formulaire pour le profil utilisateur
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie si le champ "plainPassword" a été rempli
            $newPassword = $form->get('plainPassword')->getData();
            if ($newPassword) {
                // Hache le nouveau mot de passe et l'assigne à l'utilisateur
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            // Enregistrement des modifications
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
