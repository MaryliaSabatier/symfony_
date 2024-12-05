<?php
// src/Controller/ModerateurController.php
namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Abonnement;
use App\Entity\Discussion;
use App\Form\EvenementType;
use App\Form\DiscussionType;
use App\Repository\DiscussionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModerateurController extends AbstractController
{
    #[Route(path: '/moderateur/dashboard', name: 'moderator_dashboard')]
    public function dashboard(): Response
    {
        // Vérifie que l'utilisateur a bien le rôle de modérateur
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        return $this->render('moderateur/dashboard.html.twig', [
            'controller_name' => 'Tableau de bord du Modérateur',
        ]);
    }

    #[Route(path: '/moderateur/evenement/creer', name: 'moderator_create_event')]
    public function createTemporaryEvent(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');
    
        // Création d'un nouvel événement
        $evenement = new Evenement();
        $evenement->setDateCreation(new \DateTime()); // Date de création actuelle
    
        // Création du formulaire pour l'événement
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Assigne le modérateur comme auteur de l'événement
            $evenement->setAuteur($this->getUser());
    
            // Persiste l'événement dans la base de données
            $entityManager->persist($evenement);
            $entityManager->flush();
    
            $this->addFlash('success', 'La commission temporaire a été créée avec succès.');
    
            // Redirection vers la gestion des événements
            return $this->redirectToRoute('moderator_manage_events');
        }
    
        return $this->render('moderateur/create_event.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/moderateur/evenements', name: 'moderator_manage_events')]
    public function manageEvents(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        // Récupère tous les événements pour les afficher
        $evenements = $entityManager->getRepository(Evenement::class)->findAll();

        return $this->render('moderateur/manage_events.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route(path: '/moderateur/evenement/modifier/{id}', name: 'moderator_edit_event')]
    public function editEvent(Evenement $evenement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');
    
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a été modifié avec succès.');
    
            return $this->redirectToRoute('moderator_manage_events');
        }
    
        return $this->render('moderateur/edit_event.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route(path: '/moderateur/evenement/supprimer/{id}', name: 'moderator_delete_event', methods: ['POST'])]
    public function deleteEvent(Evenement $evenement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('moderator_manage_events');
    }

    #[Route(path: '/moderateur/discussions', name: 'moderator_manage_discussions')]
    public function manageDiscussions(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        // Récupère toutes les discussions pour les afficher
        $discussions = $entityManager->getRepository(Discussion::class)->findAll();

        return $this->render('moderateur/manage_discussions.html.twig', [
            'discussions' => $discussions,
        ]);
    }

    #[Route(path: '/moderateur/discussion/creer', name: 'moderator_create_discussion')]
    public function createDiscussion(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $discussion = new Discussion();
        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discussion->setAuteur($this->getUser());

            $entityManager->persist($discussion);
            $entityManager->flush();

            $this->addFlash('success', 'La discussion a été créée avec succès.');

            return $this->redirectToRoute('moderator_manage_discussions');
        }

        return $this->render('moderateur/create_discussion.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/moderateur/discussion/modifier/{id}', name: 'moderator_edit_discussion')]
    public function editDiscussion(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $form = $this->createForm(DiscussionType::class, $discussion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'La discussion a été modifiée avec succès.');

            return $this->redirectToRoute('moderator_manage_discussions');
        }

        return $this->render('moderateur/edit_discussion.html.twig', [
            'form' => $form->createView(),
            'discussion' => $discussion,
        ]);
    }

    #[Route(path: '/moderateur/discussion/supprimer/{id}', name: 'moderator_delete_discussion', methods: ['POST'])]
    public function deleteDiscussion(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        if ($this->isCsrfTokenValid('delete' . $discussion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($discussion);
            $entityManager->flush();

            $this->addFlash('success', 'La discussion a été supprimée avec succès.');
        }

        return $this->redirectToRoute('moderator_manage_discussions');
    }

    #[Route('/moderateur/discussions-temporaires', name: 'moderator_temp_discussions', methods: ['GET'])]
public function manageTempDiscussions(DiscussionRepository $discussionRepository): Response
{
    $tempDiscussions = $discussionRepository->findBy(['isTemporary' => true]);

    return $this->render('moderateur/manage_temp_discussions.html.twig', [
        'discussions' => $tempDiscussions,
    ]);
}

#[Route('/moderateur/discussion/temporary/create', name: 'moderator_create_temp_discussion', methods: ['GET', 'POST'])]
public function createTempDiscussion(Request $request, EntityManagerInterface $entityManager): Response
{
    $discussion = new Discussion();
    $discussion->setAuteur($this->getUser()); // Définit l'auteur comme utilisateur actuel
    $discussion->setIsTemporary(true); // Définit comme temporaire

    $form = $this->createForm(DiscussionType::class, $discussion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Persister la discussion
        $entityManager->persist($discussion);
        $entityManager->flush();

        // Créer un abonnement pour l'auteur (modérateur)
        $abonnement = new Abonnement();
        $abonnement->setUser($this->getUser());
        $abonnement->setDiscussion($discussion);

        $entityManager->persist($abonnement);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été créée avec succès. Vous êtes abonné automatiquement.');
        return $this->redirectToRoute('moderator_temp_discussions');
    }

    return $this->render('moderateur/create_temp_discussion.html.twig', [
        'form' => $form->createView(),
    ]);
}


#[Route('/moderateur/discussion/temporary/edit/{id}', name: 'moderator_edit_temp_discussion', methods: ['GET', 'POST'])]
public function editTempDiscussion(Discussion $discussion, Request $request, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(DiscussionType::class, $discussion);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'La discussion temporaire a été modifiée avec succès.');
        return $this->redirectToRoute('moderator_temp_discussions');
    }

    return $this->render('moderateur/edit_temp_discussion.html.twig', [
        'form' => $form->createView(),
        'discussion' => $discussion,
    ]);
}

#[Route('/moderateur/discussion/temporary/delete/{id}', name: 'moderator_delete_temp_discussion', methods: ['POST'])]
public function deleteTempDiscussion(
    Discussion $discussion,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Vérifier si le token CSRF est valide
    if ($this->isCsrfTokenValid('delete_temp_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
        $entityManager->remove($discussion);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été supprimée avec succès.');
    } else {
        $this->addFlash('error', 'Échec de la suppression de la discussion temporaire.');
    }

    return $this->redirectToRoute('moderator_temp_discussions');
}

#[Route('/moderateur/evenement/fermer/{id}', name: 'moderator_close_event', methods: ['POST'])]
public function closeEvent(Evenement $evenement, Request $request, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_MODERATOR');

    // Vérification CSRF
    if (!$this->isCsrfTokenValid('close_event_' . $evenement->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Échec de la validation du token CSRF.');
        return $this->redirectToRoute('moderator_manage_events');
    }

    // Fermeture de l'événement
    if (!$evenement->isClosed()) {
        $evenement->setIsClosed(true);
        $entityManager->flush();

        $this->addFlash('success', 'L\'événement a été fermé avec succès.');
    } else {
        $this->addFlash('error', 'Cet événement est déjà fermé.');
    }

    return $this->redirectToRoute('moderator_manage_events');
}

#[Route('/moderateur/discussion/{id}/close', name: 'moderator_close_temp_discussion', methods: ['POST'])]
public function closeTempDiscussion(
    Discussion $discussion, 
    Request $request, 
    EntityManagerInterface $entityManager
): Response {
    $this->denyAccessUnlessGranted('ROLE_MODERATOR');

    // Vérifiez le token CSRF
    if (!$this->isCsrfTokenValid('close_discussion_' . $discussion->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Échec de la validation du token CSRF.');
        return $this->redirectToRoute('moderator_temp_discussions');
    }

    // Vérifiez si des événements associés sont encore ouverts
    $openEvents = $discussion->getEvenements()->filter(function ($event) {
        return !$event->isClosed();
    });

    if (count($openEvents) > 0) {
        $this->addFlash('error', 'Impossible de fermer la discussion temporaire. Tous les événements associés doivent être fermés.');
        return $this->redirectToRoute('moderator_temp_discussions');
    }

    // Fermez la discussion
    if (!$discussion->isClosed()) {
        $discussion->setIsClosed(true);
        $entityManager->flush();

        $this->addFlash('success', 'La discussion temporaire a été fermée avec succès.');
    }

    return $this->redirectToRoute('moderator_temp_discussions');
}


}