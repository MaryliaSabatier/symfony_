<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'user_notifications', methods: ['GET'])]
    public function notifications(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos notifications.');
        }
    
        $notifications = $notificationRepository->findByUser($user);
    
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }
    
    
    

#[Route('/notifications/{id}/mark-read', name: 'mark_notification_as_read', methods: ['POST'])]
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

#[Route('/notifications/read-all', name: 'mark_notifications_read', methods: ['POST'])]
public function markNotificationsRead(NotificationRepository $notificationRepository): Response
{
    $user = $this->getUser();
    $notificationRepository->markAllAsRead($user);

    $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

    return $this->redirectToRoute('user_notifications');
}

#[Route('/notification/{id}/delete', name: 'delete_notification', methods: ['POST'])]
public function deleteNotification(Notification $notification, Request $request, EntityManagerInterface $entityManager): Response
{
    if ($notification->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Cette notification ne vous appartient pas.');
    }

    if ($this->isCsrfTokenValid('delete_notification_' . $notification->getId(), $request->request->get('_token'))) {
        $entityManager->remove($notification);
        $entityManager->flush();

        $this->addFlash('success', 'Notification supprimée.');
    }

    return $this->redirectToRoute('user_notifications');
}



}