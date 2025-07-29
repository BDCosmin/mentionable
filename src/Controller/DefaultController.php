<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CommentVoteRepository;
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
        CommentVoteRepository $commentVoteRepository,
        RingMemberRepository $ringMemberRepository
    ): Response {
        $error = ' ';
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $memberships = $ringMemberRepository->findActiveMembershipsForUser($user);
        $ringIds = array_map(fn($m) => $m->getRing()->getId(), $memberships);

        $notes = $em->getRepository(Note::class)->findFeedNotesForUser($ringIds);

        $rolesMap = [];
        foreach ($notes as $note) {
            $ring = $note->getRing();
            $author = $note->getUser();

            if ($ring && $author) {
                $member = $ringMemberRepository->findOneBy([
                    'ring' => $ring,
                    'user' => $author,
                ]);

                if ($member) {
                    $rolesMap[$note->getId()] = $member->getRole();
                }
            }
        }

        $noteVotes = $noteVoteRepository->findBy(['user' => $user]);
        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->isUpvoted()) {
                $votesMap[$vote->getNote()->getId()] = 'upvote';
            } elseif ($vote->isDownvoted()) {
                $votesMap[$vote->getNote()->getId()] = 'downvote';
            }
        }

        $commentVotes = $commentVoteRepository->findBy(['user' => $user]);
        $commentVotesMap = [];
        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

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
            'commentVotesMap' => $commentVotesMap,
            'divVisibility' => 'none',
            'error' => $error,
            'rolesMap' => $rolesMap
        ]);
    }

    #[Route('/mark-as-read', name: 'mark_notifications_read', methods: ['POST', 'GET'])]
    public function markNotificationsRead(NotificationService $notificationService): Response
    {
        $user = $this->getUser();

        if ($user) {
            $notificationService->markAllAsRead($user);
        }

        return new Response(null, 204);
    }
}