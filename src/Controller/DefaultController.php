<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $em, NotificationService $notificationService): Response
    {
        $error = '';
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();

        $notes = $em->getRepository(Note::class)->findBy([], ['publicationDate' => 'DESC']);

        $notifications = $notificationService->getLatestUserNotifications();

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'notifications' => $notifications,
            'currentUserNametag' => $user->getNametag(),
            'divVisibility' => 'none',
            'error' => $error
        ]);
    }
}