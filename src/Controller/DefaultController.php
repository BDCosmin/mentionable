<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\NotificationRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(
        EntityManagerInterface $em,
        NoteVoteRepository $noteVoteRepository,
        RingMemberRepository $ringMemberRepository
    ): Response {
        $error = ' ';
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obține ID-urile ringurilor în care userul e membru
        $memberships = $ringMemberRepository->findBy(['user' => $user]);
        $ringIds = array_map(fn($m) => $m->getRing()->getId(), $memberships);

        // Doar notele publice și cele din ringuri unde e membru
        $notes = $em->getRepository(Note::class)->findFeedNotesForUser($ringIds);

        // Vote mapping
        $noteVotes = $noteVoteRepository->findBy(['user' => $user]);
        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->isUpvoted()) {
                $votesMap[$vote->getNote()->getId()] = 'upvote';
            } elseif ($vote->isDownvoted()) {
                $votesMap[$vote->getNote()->getId()] = 'downvote';
            }
        }

        // Sincronizează utilizatorii menționați după nametag
        foreach ($notes as $note) {
            if ($note->getMentionedUser() === null && $note->getNametag()) {
                $mentionedUser = $em->getRepository(User::class)->findOneBy(['nametag' => $note->getNametag()]);
                if ($mentionedUser) {
                    $note->setMentionedUser($mentionedUser);
                    $em->persist($note);
                }
            }
        }

        $em->flush();

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'currentUserNametag' => $user->getNametag(),
            'votesMap' => $votesMap,
            'divVisibility' => 'none',
            'error' => $error
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