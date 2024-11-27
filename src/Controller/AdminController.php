<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Discussion;
use App\Form\UserType;
use App\Form\UserCreateType;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    #[Route(path: '/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/users', name: 'admin_user_management')]
    public function manageUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('admin/manage_users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/add', name: 'admin_add_user')]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();

        // Utilisation du formulaire pour créer un utilisateur
        $form = $this->createForm(UserCreateType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Sauvegarder l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur ajouté avec succès.');

            return $this->redirectToRoute('admin_user_management');
        }

        return $this->render('admin/add_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/edit/{id}', name: 'admin_edit_user')]
    public function editUser(User $user, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Créer et traiter le formulaire de modification d'utilisateur
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est défini, le hacher avant de sauvegarder
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $passwordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('admin_user_management');
        }

        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/events', name: 'admin_event_management')]
    public function manageEvents(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        // Logique pour la gestion des événements (à compléter)
        return $this->render('admin/manage_events.html.twig');
    }

    #[Route('/admin/users/delete/{id}', name: 'admin_delete_user', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérification du token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_user_management');
    }

    #[Route('/admin/discussions-temporaires', name: 'admin_temp_discussions', methods: ['GET'])]
    public function manageTempDiscussions(DiscussionRepository $discussionRepository): Response
    {
        $tempDiscussions = $discussionRepository->findBy(['isTemporary' => true]);
    
        return $this->render('admin/manage_temp_discussions.html.twig', [
            'discussions' => $tempDiscussions,
        ]);
    }

    #[Route('/admin/discussion/{id}/close', name: 'close_discussion', methods: ['POST'])]
    public function closeDiscussion(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez le token CSRF
        if (!$this->isCsrfTokenValid('close_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Échec de la validation du token CSRF.');
            return $this->redirectToRoute('admin_temp_discussions');
        }
    
        // Fermez la discussion si elle est temporaire et ouverte
        if ($discussion->isTemporary() && !$discussion->isClosed()) {
            $discussion->setIsClosed(true);
            $entityManager->flush();
    
            $this->addFlash('success', 'La discussion temporaire a été fermée avec succès.');
        } else {
            $this->addFlash('error', 'Impossible de fermer cette discussion.');
        }
    
        // Redirection explicite vers la page des discussions temporaires
        return $this->redirectToRoute('admin_temp_discussions');
    }
    


#[Route('/admin/discussion/temporary/create', name: 'create_temp_discussion', methods: ['GET', 'POST'])]
public function createTempDiscussion(Request $request, EntityManagerInterface $entityManager): Response
{
    // Vérification des rôles (Admin ou Modérateur)
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    $this->denyAccessUnlessGranted('ROLE_MODERATOR');
    
    $discussion = new Discussion();
    $discussion->setAuteur($this->getUser()); // Définit l'auteur comme utilisateur actuel
    $discussion->setIsTemporary(true); // Définit comme temporaire

    $form = $this->createForm(DiscussionType::class, $discussion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($discussion);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été créée avec succès.');
        return $this->redirectToRoute('admin_temp_discussions');
    }

    return $this->render('admin/create_temp_discussion.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/admin/discussion/temporary/{id}/edit', name: 'edit_temp_discussion', methods: ['GET', 'POST'])]
public function editTempDiscussion(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(DiscussionType::class, $discussion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été modifiée avec succès.');
        return $this->redirectToRoute('admin_temp_discussions');
    }

    return $this->render('admin/edit_temp_discussion.html.twig', [
        'form' => $form->createView(),
        'discussion' => $discussion,
    ]);
}

#[Route('/admin/discussion/temporary/delete/{id}', name: 'delete_temp_discussion', methods: ['POST'])]
public function deleteTempDiscussion(
    Discussion $discussion,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Vérifier le token CSRF pour protéger contre les attaques CSRF
    if ($this->isCsrfTokenValid('delete_temp_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
        $entityManager->remove($discussion);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été supprimée avec succès.');
    } else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }

    return $this->redirectToRoute('admin_temp_discussions');
}



}
