<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EntityManagerInterface $em,
                          NotificationService $notificationService,
                          NotificationRepository $notificationRepository,
                          NoteVoteRepository $noteVoteRepository,
                          FriendRequestRepository $friendRequestRepository,
    ): Response
    {
        $error = '';
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $notes = $em->getRepository(Note::class)->findBy([], ['publicationDate' => 'DESC']);
        $noteVotes = $noteVoteRepository->findBy([]);

        foreach ($notes as $note) {
            $note->mentionedUserId = $note->getMentionedUserId($em);
        }

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'currentUserNametag' => $user->getNametag(),
            'divVisibility' => 'none',
            'error' => $error,
            'noteVotes' => $noteVotes,
        ]);
    }

    #[Route('/mark-as-read', name: 'mark_notifications_read', methods: ['POST', 'GET'])]
    public function markNotificationsRead(NotificationService $notificationService): Response
    {
        $user = $this->getUser();

        if ($user) {
            $notificationService->markAllAsRead($user);
        }

        return new Response(null, 204); // 204 = No Content
    }
}