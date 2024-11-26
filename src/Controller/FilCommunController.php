<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Form\PostType;
use App\Form\CommentaireType;
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
    #[Route(path: '/fil-commun', name: 'fil_commun', methods: ['GET', 'POST'])]
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

        // Gestion du formulaire pour ajouter un post
        $post = new Post();
        $post->setAuteur($user);
        $post->setDateCreation(new \DateTime());

        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Votre post a été publié avec succès.');
            return $this->redirectToRoute('fil_commun');
        }

        // Génération des formulaires pour les commentaires
        $commentForms = [];
        foreach ($posts as $postItem) {
            $commentaire = new Commentaire();
            $commentaire->setPost($postItem);
            $commentaire->setAuteur($user);
            $commentaire->setDateCreation(new \DateTime());

            $commentForm = $this->createForm(CommentaireType::class, $commentaire, [
                'action' => $this->generateUrl('fil_commun_add_comment', ['id' => $postItem->getId()]),
            ]);

            $commentForms[$postItem->getId()] = $commentForm->createView();
        }

        return $this->render('fil_commun/index.html.twig', [
            'discussions' => $discussions,
            'posts' => $posts,
            'evenements' => $evenements,
            'query' => $query,
            'abonnementIds' => $abonnementIds,
            'postForm' => $postForm->createView(),
            'commentForms' => $commentForms,
        ]);
    }

    #[Route('/fil-commun/post/{id}/comment', name: 'fil_commun_add_comment', methods: ['POST'])]
    public function addComment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour commenter.');
        }

        $commentaire = new Commentaire();
        $commentaire->setAuteur($user);
        $commentaire->setPost($post);
        $commentaire->setDateCreation(new \DateTime());

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté.');
        }

        return $this->redirectToRoute('fil_commun');
    }

    #[Route('/fil-commun/post/{id}/edit', name: 'edit_post', methods: ['GET', 'POST'])]
    public function editPost(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($post->getAuteur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres posts.');
            return $this->redirectToRoute('fil_commun');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Post modifié avec succès.');
            return $this->redirectToRoute('fil_commun');
        }

        return $this->render('fil_commun/edit_post.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/fil-commun/post/{id}/delete', name: 'delete_post', methods: ['POST'])]
    public function deletePost(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($post->getAuteur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres posts.');
            return $this->redirectToRoute('fil_commun');
        }

        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Échec lors de la suppression du post.');
        }

        return $this->redirectToRoute('fil_commun');
    }

    #[Route('/fil-commun/comment/{id}/edit', name: 'edit_comment', methods: ['GET', 'POST'])]
public function editComment(
    Commentaire $commentaire,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();

    // Vérification de l'auteur
    if ($commentaire->getAuteur() !== $user) {
        $this->addFlash('error', 'Vous ne pouvez modifier que vos propres commentaires.');
        return $this->redirectToRoute('fil_commun');
    }

    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire modifié avec succès.');
        return $this->redirectToRoute('fil_commun'); // Redirection vers le fil commun
    }

    return $this->render('fil_commun/edit_comment.html.twig', [
        'form' => $form->createView(),
        'commentaire' => $commentaire,
    ]);
}
#[Route('/fil-commun/comment/{id}/delete', name: 'delete_comment', methods: ['POST'])]
public function deleteComment(
    Commentaire $commentaire,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();

    // Vérification de l'auteur
    if ($commentaire->getAuteur() !== $user) {
        $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres commentaires.');
        return $this->redirectToRoute('fil_commun');
    }

    if ($this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), $request->request->get('_token'))) {
        $entityManager->remove($commentaire);
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Échec lors de la suppression du commentaire.');
    }

    return $this->redirectToRoute('fil_commun'); // Redirection vers le fil commun
}


}
