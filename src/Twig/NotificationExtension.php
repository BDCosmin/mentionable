<?php

namespace App\Twig;

use App\Repository\FriendRequestRepository;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private NotificationService $notificationService,
                                private Security $security,
                                private FriendRequestRepository $friendRequestRepository
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        return [
            'latest_notifications' => $this->notificationService->getLatestUserNotifications(),
            'last_notification' => $this->notificationService->getLastUserNotification(),
            'friend_requests' => $user !== null
                ? $this->notificationService->getFriendRequests($user, $this->friendRequestRepository)
                : [],
        ];
    }
}