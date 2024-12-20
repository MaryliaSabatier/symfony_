<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Post;
use App\Entity\Commentaire;
use App\Entity\Evenement;
use App\Entity\Notification;
use App\Entity\Abonnement;
use App\Form\DiscussionType;
use App\Form\PostType;
use App\Form\EvenementType;
use App\Form\CommentaireType;
use App\Repository\DiscussionRepository;
use App\Repository\EvenementRepository;
use App\Repository\PostRepository;
use App\Repository\NotificationRepository;
use App\Repository\AbonnementRepository;
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
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();
    
            // Définir l'auteur de la discussion
            $discussion->setAuteur($user);
    
            // Ajouter un abonnement pour l'auteur
            $abonnement = new Abonnement();
            $abonnement->setUser($user);
            $abonnement->setDiscussion($discussion);
    
            // Persister les données
            $entityManager->persist($discussion);
            $entityManager->persist($abonnement);
            $entityManager->flush();
    
            // Ajouter un message de confirmation
            $this->addFlash('success', 'Discussion créée avec succès. Vous êtes maintenant abonné à cette discussion.');
    
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
    public function show(
        Discussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager,
        EvenementRepository $evenementRepository,
        PostRepository $postRepository,
        AbonnementRepository $abonnementRepository
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

        // Recherche des abonnements pour l'utilisateur connecté
        $user = $this->getUser();
        $abonnementIds = $user
            ? $abonnementRepository->findSubscribedEventIdsByUser($user)
            : [];

        // Vérifie si l'utilisateur est abonné à la discussion
        $isSubscribed = false;
        if ($user) {
            $isSubscribed = $abonnementRepository->findOneBy([
                'user' => $user,
                'discussion' => $discussion,
            ]) !== null;
        }

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

            // Envoyer des notifications aux abonnés de la discussion
            $abonnements = $abonnementRepository->findBy(['discussion' => $discussion]);
            foreach ($abonnements as $abonnement) {
                $notification = new Notification();
                $notification->setUser($abonnement->getUser());
                $notification->setMessage(sprintf("Nouveau post ajouté dans la discussion : %s", $discussion->getNom()));
                $notification->setCreatedAt(new \DateTime());
                $entityManager->persist($notification);
            }

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
            'abonnementIds' => $abonnementIds, // Transmettre les IDs des abonnements à la vue
            'isSubscribed' => $isSubscribed, // Indique si l'utilisateur est abonné à la discussion
        ]);
    }

    #[Route('/evenement/{id}/abonner', name: 'abonner_evenement', methods: ['POST'])]
    public function abonnerEvenement(Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $abonnement = $entityManager->getRepository(Abonnement::class)->findOneBy([
            'user' => $user,
            'evenement' => $evenement,
        ]);

        if ($abonnement) {
            $this->addFlash('error', 'Vous êtes déjà abonné à cet événement.');
        } else {
            $nouvelAbonnement = new Abonnement();
            $nouvelAbonnement->setUser($user);
            $nouvelAbonnement->setEvenement($evenement);

            $entityManager->persist($nouvelAbonnement);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes abonné à cet événement.');
        }

        return $this->redirectToRoute('discussion_show', ['id' => $evenement->getDiscussion()->getId()]);
    }

    #[Route('/evenement/{id}/desabonner', name: 'desabonner_evenement', methods: ['POST'])]
    public function desabonnerEvenement(Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $abonnement = $entityManager->getRepository(Abonnement::class)->findOneBy([
            'user' => $user,
            'evenement' => $evenement,
        ]);

        if ($abonnement) {
            $entityManager->remove($abonnement);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes désabonné de cet événement.');
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas abonné à cet événement.');
        }

        return $this->redirectToRoute('discussion_show', ['id' => $evenement->getDiscussion()->getId()]);
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

    #[Route('/post/{id}/delete', name: 'm', methods: ['POST'])]
    public function deletePost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur est l'auteur ou a le rôle ADMIN
        if ($post->getAuteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres posts.');
        }
    
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete_post' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
    
            $this->addFlash('success', 'Post supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Échec de la validation du token CSRF.');
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

    #[Route('/discussion/{id}/add-event', name: 'discussion_add_event', methods: ['GET', 'POST'])]
    public function addEvent(
        Request $request,
        Discussion $discussion,
        EntityManagerInterface $entityManager,
        AbonnementRepository $abonnementRepository
    ): Response {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenement->setDiscussion($discussion);
            $entityManager->persist($evenement);

            // Envoyer des notifications aux abonnés de la discussion
            $abonnements = $abonnementRepository->findBy(['discussion' => $discussion]);
            foreach ($abonnements as $abonnement) {
                $notification = new Notification();
                $notification->setUser($abonnement->getUser());
                $notification->setMessage(sprintf(
                    "Nouvel événement ajouté dans la discussion : %s",
                    $discussion->getNom()
                ));
                $notification->setCreatedAt(new \DateTime());
                $entityManager->persist($notification);
            }

            // Notifications pour les abonnés
            $abonnements = $abonnementRepository->findBy(['discussion' => $discussion]);
            foreach ($abonnements as $abonnement) {
                $notification = new Notification();
                $notification->setUser($abonnement->getUser());
                $notification->setMessage("Nouveau post ajouté dans la discussion : " . $discussion->getNom());
                $notification->setCreatedAt(new \DateTime());
                $notification->setDiscussion($discussion);
                $entityManager->persist($notification);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Événement ajouté avec succès.');
            return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
        }

        return $this->render('event/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Marquer une notification comme lue.
     */
    #[Route('/notifications/{id}/read', name: 'mark_notification_as_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($notification->getUser() !== $user) {
            throw $this->createAccessDeniedException('Cette notification ne vous appartient pas.');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        $this->addFlash('success', 'Notification marquée comme lue.');
        return $this->redirectToRoute('user_notifications');
    }

    /**
     * S'abonner à une discussion.
     */
    #[Route('/discussion/{id}/abonner', name: 'discussion_abonner', methods: ['POST'])]
    public function abonnerDiscussion(Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Vérifie si l'utilisateur est déjà abonné
        $abonnement = $entityManager->getRepository(Abonnement::class)->findOneBy([
            'user' => $user,
            'discussion' => $discussion,
        ]);

        if (!$abonnement) {
            $nouvelAbonnement = new Abonnement();
            $nouvelAbonnement->setUser($user);
            $nouvelAbonnement->setDiscussion($discussion);

            $entityManager->persist($nouvelAbonnement);
            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes maintenant abonné à cette discussion.');
        } else {
            $this->addFlash('error', 'Vous êtes déjà abonné à cette discussion.');
        }

        // Rester sur la page actuelle (discussion_show)
        return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
    }

    /**
     * Se désabonner d'une discussion.
     */
    #[Route('/discussion/{id}/desabonner', name: 'discussion_desabonner', methods: ['POST'])]
    public function desabonnerDiscussion(Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        // Vérifie si l'utilisateur est l'auteur de la discussion
        if ($discussion->getAuteur() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas vous désabonner d\'une discussion que vous avez créée.');
            return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
        }
    
        // Trouver l'abonnement existant
        $abonnement = $entityManager->getRepository(Abonnement::class)->findOneBy([
            'user' => $user,
            'discussion' => $discussion,
        ]);
    
        if ($abonnement) {
            $entityManager->remove($abonnement);
            $entityManager->flush();
    
            $this->addFlash('success', 'Vous êtes maintenant désabonné de cette discussion.');
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas abonné à cette discussion.');
        }
    
        return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
    }
    

    /**
     * Supprimer un post en tant qu'administrateur.
     */
    #[Route('/admin/post/{id}/delete', name: 'admin_delete_post', methods: ['POST'])]
    public function deletePostAsAdmin(Post $post, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez que l'utilisateur a le rôle ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $discussionId = $post->getDiscussion()->getId(); // Obtenez l'ID de la discussion pour redirection

        // Supprimez le post
        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post supprimé avec succès.');

        return $this->redirectToRoute('discussion_show', ['id' => $discussionId]);
    }

    /**
     * Supprimer un post en tant qu'administrateur ou modérateur.
     */
    #[Route('/moderator/post/{id}/delete', name: 'moderator_delete_post', methods: ['POST'])]
    public function deletePostAsModerator(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez que l'utilisateur est modérateur ou auteur du post
        if (!$this->isGranted('ROLE_MODERATOR') && $post->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce post.');
        }
    
        // Validation du token CSRF
        if (!$this->isCsrfTokenValid('delete_post' . $post->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Échec de la validation du token CSRF.');
            return $this->redirectToRoute('discussion_show', ['id' => $post->getDiscussion()->getId()]);
        }
    
        try {
            // Suppression du post
            $entityManager->remove($post);
            $entityManager->flush();
    
            $this->addFlash('success', 'Le post a été supprimé avec succès.');
        } catch (\Exception $e) {
            // Gestion des erreurs
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du post.');
        }
    
        // Redirection vers la discussion associée
        return $this->redirectToRoute('discussion_show', ['id' => $post->getDiscussion()->getId()]);
    }
    
    
    /**
     * Supprimer un commentaire en tant qu'administrateur.
     */
    #[Route('/admin/comment/{id}/delete', name: 'admin_delete_comment', methods: ['POST'])]
    public function deleteCommentAsAdmin(Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        // Vérifiez que l'utilisateur a le rôle ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $discussionId = $commentaire->getPost()->getDiscussion()->getId(); // Obtenez l'ID de la discussion pour redirection

        // Supprimez le commentaire
        $entityManager->remove($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');

        return $this->redirectToRoute('discussion_show', ['id' => $discussionId]);
    }
    /**
     * Supprimer un commentaire en tant qu'administrateur ou modérateur.
     */
    #[Route('/moderator/comment/{id}/delete', name: 'moderator_delete_comment', methods: ['POST'])]
    public function deleteCommentAsModerator(Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $discussionId = $commentaire->getPost()->getDiscussion()->getId(); // Obtenez l'ID de la discussion pour redirection

        // Supprimez le commentaire
        $entityManager->remove($commentaire);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');

        return $this->redirectToRoute('discussion_show', ['id' => $discussionId]);
    }

    #[Route('/discussion/{id}/close', name: 'close_discussion', methods: ['POST'])]
    public function closeDiscussion(Discussion $discussion, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les droits (administrateurs et modérateurs seulement)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MODERATOR')) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de fermer cette discussion.');
            return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
        }

        // Mettre à jour l'état de la discussion
        $discussion->setIsClosed(true);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion a été fermée avec succès.');
        return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
    }

    /**
     * Ajouter un message dans une discussion.
     */
    #[Route('/discussion/{id}/post', name: 'post_message', methods: ['POST'])]
    public function postMessage(
        Discussion $discussion,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        AbonnementRepository $abonnementRepository
    ): Response {
        $post = new Post();
        $post->setDiscussion($discussion);
        $post->setAuteur($this->getUser());
        $post->setDateCreation(new \DateTime());

        // Création et traitement du formulaire
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde du message
            $entityManager->persist($post);

            // Envoi des notifications aux abonnés
            $abonnés = $abonnementRepository->findBy(['discussion' => $discussion]);
            foreach ($abonnés as $abonné) {
                if ($abonné->getUser() !== $this->getUser()) { // Ne pas notifier l'auteur
                    $notification = new Notification();
                    $notification->setUser($abonné->getUser());
                    $notification->setMessage(sprintf(
                        'Nouveau message dans la discussion "%s"',
                        $discussion->getNom()
                    ));
                    $notification->setCreatedAt(new \DateTime());
                    $notification->setIsRead(false);

                    $entityManager->persist($notification);
                }
            }

            // Enregistrement des notifications et du message
            $entityManager->flush();

            // Message flash de succès
            $this->addFlash('success', 'Message ajouté avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'ajout du message.');
        }

        return $this->redirectToRoute('discussion_show', ['id' => $discussion->getId()]);
    }

    public function filCommun(
        Request $request,
        NotificationRepository $notificationRepository,
        DiscussionRepository $discussionRepository,
        PostRepository $postRepository
    ): Response {
        $user = $this->getUser();

        // Récupération des notifications non lues pour l'utilisateur connecté
        $unreadCount = $user ? count($notificationRepository->findUnreadByUser($user)) : 0;

        // Récupération des discussions et posts pour le Fil Commun
        $discussions = $discussionRepository->findAll();
        $posts = $postRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('discussion/fil_commun.html.twig', [
            'discussions' => $discussions,
            'posts' => $posts,
            'unreadCount' => $unreadCount, // Nombre de notifications non lues
        ]);
    }


}
