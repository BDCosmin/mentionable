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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(
        EntityManagerInterface $em,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        RingMemberRepository $ringMemberRepository,
        Request $request,
    ): Response {
        $error = ' ';
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $memberships = $ringMemberRepository->findActiveMembershipsForUser($user);
        $ringIds = array_map(fn($m) => $m->getRing()->getId(), $memberships);

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $notes = $em->getRepository(Note::class)->findFeedNotesForUser($ringIds, $limit, $offset);

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
                    $rolesMap[$author->getId()] = $member->getRole();
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

        $limitedComments = [];
        foreach ($notes as $note) {
            $allComments = $note->getComments();
            $sorted = $allComments->toArray();

            usort($sorted, fn($a, $b) => $b->getPublicationDate() <=> $a->getPublicationDate());

            $limitedComments[$note->getId()] = array_slice($sorted, 0, 5);
        }

        $favoritesMap = [];
        foreach ($notes as $note) {
            $favoritesMap[$note->getId()] = $user->hasFavorite($note);
        }

        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->render('default/_note.html.twig', [
                'notes' => $notes,
                'votesMap' => $votesMap,
                'commentVotesMap' => $commentVotesMap,
                'rolesMap' => $rolesMap,
                'limitedComments' => $limitedComments,
                'favoritesMap' => $favoritesMap,
                'currentUserNametag' => $user->getNametag(),
            ]);
        }

        return $this->render('default/index.html.twig', [
            'notes' => $notes,
            'currentUserNametag' => $user->getNametag(),
            'votesMap' => $votesMap,
            'commentVotesMap' => $commentVotesMap,
            'favoritesMap' => $favoritesMap,
            'divVisibility' => 'none',
            'error' => $error,
            'rolesMap' => $rolesMap,
            'limitedComments' => $limitedComments,
            'page' => $page,
        ]);
    }

    #[Route('/api/emojis', name: 'api_emojis')]
    public function fetchEmojis(): JsonResponse {
        $apiKey = $_ENV['EMOJI_API_KEY'];
        $data = file_get_contents("https://emoji-api.com/emojis?access_key={$apiKey}");
        return $this->json(json_decode($data, true));
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