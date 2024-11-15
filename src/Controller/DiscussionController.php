<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Form\DiscussionType;
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

class DiscussionController extends AbstractController
{
    #[Route('/admin/discussions', name: 'admin_discussion_list', methods: ['GET'])]
    public function list(DiscussionRepository $discussionRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $discussions = $discussionRepository->findAll();

        return $this->render('admin/discussion_list.html.twig', [
            'discussions' => $discussions,
        ]);
    }

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

    #[Route('/discussions', name: 'discussion_list', methods: ['GET'])]
    public function listForAll(DiscussionRepository $discussionRepository): Response
    {
        $discussions = $discussionRepository->findAll();

        return $this->render('discussion/index.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    #[Route('/discussions/{id}', name: 'discussion_show', methods: ['GET', 'POST'])]
    #[Route('/discussions/{id}', name: 'discussion_show', methods: ['GET', 'POST'])]
    public function show(
        Discussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager,
        EvenementRepository $evenementRepository,
        PostRepository $postRepository
    ): Response {
        // Récupération du paramètre de recherche
        $query = $request->query->get('q', '');
    
        // Recherche des événements correspondant au terme
        $evenements = $query
            ? $evenementRepository->createQueryBuilder('e')
                ->andWhere('e.discussion = :discussion')
                ->andWhere('e.contenu LIKE :query OR e.lieu LIKE :query')
                ->setParameter('discussion', $discussion)
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('e.dateCreation', 'DESC')
                ->getQuery()
                ->getResult()
            : $evenementRepository->findBy(['discussion' => $discussion], ['dateCreation' => 'DESC']);
    
        // Recherche des posts correspondant au terme
        $posts = $query
            ? $postRepository->findByDiscussionAndQuery($discussion, $query)
            : $postRepository->findBy(['discussion' => $discussion], ['dateCreation' => 'DESC']);
    
        // Gestion du formulaire d'ajout de post
        $post = new Post();
        $post->setDiscussion($discussion);
        $post->setAuteur($this->getUser());
    
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);
    
        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $entityManager->persist($post);
            $entityManager->flush();
    
            $this->addFlash('success', 'Message ajouté avec succès.');
            return $this->redirectToRoute('discussion_show', [
                'id' => $discussion->getId(),
                'q' => $query, // Maintenir la recherche
            ]);
        }
    
        // Génération des formulaires de commentaire pour chaque post
        $commentForms = [];
        foreach ($posts as $post) {
            $commentForm = $this->createForm(CommentaireType::class, null, [
                'action' => $this->generateUrl('add_comment', ['id' => $post->getId()]),
            ]);
            $commentForms[$post->getId()] = $commentForm->createView();
        }
    
        return $this->render('discussion/show.html.twig', [
            'discussion' => $discussion,
            'posts' => $posts,
            'evenements' => $evenements,
            'query' => $query,
            'postForm' => $postForm->createView(),
            'commentForms' => $commentForms,
        ]);
    }
    

    #[Route('/post/{id}/comment', name: 'add_comment', methods: ['POST'])]
    public function addComment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $commentaire = new Commentaire();
        $commentaire->setAuteur($this->getUser());
        $commentaire->setPost($post);
        $commentaire->setDateCreation(new \DateTime());

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès.');

            return $this->redirectToRoute('discussion_show', ['id' => $post->getDiscussion()->getId()]);
        }

        return $this->render('comment/add.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'edit_post', methods: ['GET', 'POST'])]
    public function editPost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($post->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres posts.');
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Post modifié avec succès.');
            return $this->redirectToRoute('discussion_show', ['id' => $post->getDiscussion()->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'delete_post', methods: ['POST'])]
    public function deletePost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($post->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres posts.');
        }

        if ($this->isCsrfTokenValid('delete_post' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        
            $this->addFlash('success', 'Post supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('discussion_show', ['id' => $post->getDiscussion()->getId()]);
    }

    #[Route('/comment/edit/{id}', name: 'edit_comment', methods: ['GET', 'POST'])]
public function editComment(
    Commentaire $commentaire,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Vérification : L'utilisateur doit être l'auteur du commentaire
    if ($this->getUser() !== $commentaire->getAuteur()) {
        $this->addFlash('error', 'Vous ne pouvez modifier que vos propres commentaires.');
        return $this->redirectToRoute('discussion_show', ['id' => $commentaire->getPost()->getDiscussion()->getId()]);
    }

    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire modifié avec succès.');

        return $this->redirectToRoute('discussion_show', ['id' => $commentaire->getPost()->getDiscussion()->getId()]);
    }

    return $this->render('comment/edit.html.twig', [
        'form' => $form->createView(),
        'commentaire' => $commentaire,
    ]);
}

#[Route('/comment/delete/{id}', name: 'delete_comment', methods: ['POST'])]
public function deleteComment(
    Commentaire $commentaire,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Vérification : L'utilisateur doit être l'auteur du commentaire
    if ($this->getUser() !== $commentaire->getAuteur()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        return $this->redirectToRoute('discussion_show', ['id' => $commentaire->getPost()->getDiscussion()->getId()]);
    }

    if ($this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), $request->request->get('_token'))) {
        $entityManager->remove($commentaire);
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire supprimé avec succès.');
    } else {
        $this->addFlash('error', 'Token CSRF invalide.');
    }

    return $this->redirectToRoute('discussion_show', ['id' => $commentaire->getPost()->getDiscussion()->getId()]);
}

}
