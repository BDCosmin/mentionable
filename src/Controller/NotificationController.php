<?php

namespace App\Controller;

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
            $data[] = [
                'id' => $notification->getId(),
                'sender' => $notification->getSender()?->getNametag(),
                'avatar' => $notification->getSender()?->getAvatar(),
                'type' => $notification->getType(),
                'isRead' => $notification->isRead(),
                'humanTime' => $notification->getHumanTimeNotification(),
            ];
        }

        return new JsonResponse($data);
    }
}