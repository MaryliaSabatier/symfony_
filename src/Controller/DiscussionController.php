<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Form\DiscussionType;
use App\Repository\CommentaireRepository;
use App\Repository\DiscussionRepository;
use App\Repository\EvenementRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscussionController extends AbstractController
{
    // Route pour la gestion des discussions par l'admin
    #[Route('/admin/discussions', name: 'admin_discussion_list', methods: ['GET'])]
    public function list(DiscussionRepository $discussionRepository): Response
    {
        // Vérifie si l'utilisateur a le rôle admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $discussions = $discussionRepository->findAll();

        return $this->render('admin/discussion_list.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    // Création d'une discussion pour les admins
    #[Route('/admin/discussions/create', name: 'admin_create_discussion', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $discussion = new Discussion();
        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discussion->setAuteur($this->getUser());
            $entityManager->persist($discussion);
            $entityManager->flush();

            $this->addFlash('success', 'Discussion créée avec succès.');
            return $this->redirectToRoute('admin_discussion_list');
        }

        return $this->render('admin/create_discussion.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Modification d'une discussion pour les admins
    #[Route('/admin/discussions/edit/{id}', name: 'admin_edit_discussion', methods: ['GET', 'POST'])]
    public function edit(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Discussion modifiée avec succès.');

            return $this->redirectToRoute('admin_discussion_list');
        }

        return $this->render('admin/edit_discussion.html.twig', [
            'form' => $form->createView(),
            'discussion' => $discussion,
        ]);
    }

    // Suppression d'une discussion par les admins
    #[Route('/admin/discussions/delete/{id}', name: 'admin_delete_discussion', methods: ['POST'])]
    public function delete(Request $request, Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $discussion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($discussion);
            $entityManager->flush();

            $this->addFlash('success', 'Discussion supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_discussion_list');
    }

    // Liste des discussions pour tous les utilisateurs (public)
    #[Route('/discussions', name: 'discussion_list', methods: ['GET'])]
    public function listForAll(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findAll();

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    // Affichage d'une discussion spécifique pour tous les utilisateurs
    #[Route('/discussions/{id}', name: 'discussion_show', methods: ['GET'])]
    public function show(Discussion $discussion): Response
    {
        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
        ]);
    }

    // Page récapitulative avec tous les posts, commentaires et événements
    #[Route('/tout', name: 'all_posts_comments_events', methods: ['GET'])]
    public function allPostsCommentsEvents(EvenementRepository $eventRepository, PostRepository $postRepository, CommentaireRepository $commentaireRepository): Response
    {
        // Récupérer les 10 derniers événements, posts, et commentaires
        $events = $eventRepository->findBy([], ['dateCreation' => 'DESC'], 10);
        $posts = $postRepository->findBy([], ['dateCreation' => 'DESC'], 10);
        $commentaires = $commentaireRepository->findBy([], ['dateCreation' => 'DESC'], 10);

        return $this->render('discussion/all_content.html.twig', [
            'events' => $events,
            'posts' => $posts,
            'commentaires' => $commentaires,
        ]);
    }
}
