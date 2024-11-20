<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    /**
     * Affiche les notifications non lues de l'utilisateur connecté.
     */
    #[Route('/notifications', name: 'user_notifications', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos notifications.');
        }

        // Récupérer toutes les notifications non lues de l'utilisateur
        $notifications = $notificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'] // Notifications triées par date de création
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marque une notification spécifique comme lue.
     */
    #[Route('/notifications/{id}/read', name: 'mark_notification_as_read', methods: ['POST'])]
    public function markAsRead(int $id, NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour effectuer cette action.');
        }

        $notification = $notificationRepository->find($id);

        if (!$notification || $notification->getUser() !== $user) {
            throw $this->createNotFoundException('Notification introuvable ou non autorisée.');
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        $this->addFlash('success', 'Notification marquée comme lue.');

        return $this->redirectToRoute('user_notifications');
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    #[Route('/notifications/read-all', name: 'read_all_notifications', methods: ['POST'])]
    public function markAllAsRead(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour effectuer cette action.');
        }

        $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Toutes vos notifications ont été marquées comme lues.');

        return $this->redirectToRoute('user_notifications');
    }
}
