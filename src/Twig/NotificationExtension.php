<?php

namespace App\Twig;

use App\Service\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private NotificationService $notificationService) {}

    public function getGlobals(): array
    {
        return [
            'latest_notifications' => $this->notificationService->getLatestUserNotifications(),
        ];
    }
}