<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(NoteRepository $noteRepository, NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notes = $noteRepository->findBy(['user' => $user]);

        $notifications = $notificationService->getLatestUserNotifications();

        $adsCount = count($notes);

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'adsCount' => $adsCount,
            'notes' => $notes,
            'notifications' => $notifications
        ]);
    }
}
