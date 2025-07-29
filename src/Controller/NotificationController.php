<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

final class NotificationController extends AbstractController
{
    #[Route('/notifications/fetch', name: 'fetch_notifications', methods: ['GET'])]
    public function fetchNotifications(NotificationService $notificationService): JsonResponse
    {
        $notifications = $notificationService->getLatestUserNotifications();

        $data = [];

        foreach ($notifications as $notification) {
            $ring = $notification->getRing();
            $data[] = [
                'id' => $notification->getId(),
                'sender' => $notification->getSender()?->getNametag(),
                'avatar' => $notification->getSender()?->getAvatar(),
                'type' => $notification->getType(),
                'isRead' => $notification->isRead(),
                'humanTime' => $notification->getHumanTimeNotification(),
                'ring' => $notification->getRing() ? [
                    'title' => $notification->getRing()->getTitle(),
                    'banner' => $notification->getRing()->getBanner(),
                ] : null,
                'ticket' => $notification->getTicket() ? [
                    'id' => $notification->getTicket()->getId(),
                ] : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/notification/{notificationId}/redirect', name: 'app_notification_redirect')]
    public function redirectToNotification(int $notificationId, NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notification = $notificationService->notificationRepository->find($notificationId);

        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }

        if ($user) {
            $notificationService->markOneAsRead($user, $notification);
        }

        if (in_array($notification->getType(), ['reported', 'closed_report'])) {
            return $this->redirectToRoute('app_user_notifications');
        }

        if ($notification->getNote()) {
            return $this->redirectToRoute('app_note_show', ['noteId' => $notification->getNote()->getId()]);
        }

        if ($notification->getComment()) {
            return $this->redirectToRoute('app_note_show', [
                'noteId' => $notification->getComment()->getNote()->getId()
            ]);
        }

        if ($notification->getType() === 'friend_request') {
            return $this->redirectToRoute('app_profile', [
                'nametag' => $user->getNametag()
            ]);
        }

        if ($notification->getType() === 'ring_role_upgrade') {
            return $this->redirectToRoute('app_ring_show', [
                'id' => $notification->getRing()->getId()
            ]);
        }

        if ($notification->getType() === 'admin_ticket_solved') {
            return $this->redirectToRoute('app_ticket_preview', [
                'id' => $notification->getTicket()->getId()
            ]);
        }

        if ($notification->getLink()) {
            return $this->redirect($notification->getLink());
        }

        $this->addFlash('error', 'The notification does not have a valid link.');
        return $this->redirectToRoute('homepage');
    }
}