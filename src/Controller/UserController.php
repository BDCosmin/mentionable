<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserController extends AbstractController
{
    #[Route('/my-notes/user', name: 'app_user')]
    public function index(NoteRepository $noteRepository, NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notes = $noteRepository->findBy(['user' => $user]);

        $notifications = $notificationService->getLatestUserNotifications();

        $notesCount = count($notes);

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'notesCount' => $notesCount,
            'notes' => $notes,
            'notifications' => $notifications
        ]);
    }

    #[Route('/my-notifications/user', name: 'app_notifications')]
    public function showAllNotifications(UserInterface $user,NotificationRepository $notificationRepository, NotificationService $notificationService, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $allNotifications = $notificationRepository->findBy(['receiver' => $user], ['notifiedDate' => 'DESC']);

        $notifCount = count($allNotifications);

        return $this->render('default/all_notifications.html.twig', [
            'allNotifications' => $allNotifications,
            'notifCount' => $notifCount
        ]);
    }
}
