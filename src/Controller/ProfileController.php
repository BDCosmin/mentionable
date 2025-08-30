<?php

namespace App\Controller;

use App\Entity\Interest;
use App\Entity\Note;
use App\Repository\CommentVoteRepository;
use App\Repository\InterestRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\NotificationRepository;
use App\Repository\RingMemberRepository;
use App\Repository\RingRepository;
use App\Repository\UserRepository;
use App\Repository\FriendRequestRepository;
use App\Service\NotificationService;
use App\Service\TextModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile/{id}/view', name: 'app_profile')]
    public function index(
        int $id,
        FriendRequestRepository $friendRequestRepository,
        RingRepository $ringRepository,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
        RingMemberRepository $ringMemberRepository,
    ): Response
    {
        $user = $userRepository->find($id);
        $currentUser = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $noteVotes = $noteVoteRepository->findBy([]);
        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);
        $notifications = $notificationRepository->findBy(['receiver' => $user]);
        $notes = $em->getRepository(Note::class)->findBy(['user' => $user], ['publicationDate' => 'DESC']);
        $interests = $user->getInterests();
        $rings = $ringRepository->findByUserOrMember($user);
        $ringsMobileDisplay = $ringRepository->findByUserOrMemberLimit($user);

        $notesWithMentionedUser = [];
        foreach ($notes as $note) {
            $mentionedUser = $note->getMentionedUser();
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser,
            ];
        }

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $currentUser) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

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
                    $rolesMap[$author->getId() . '_' . $ring->getId()] = $member->getRole();
                }
            }
        }

        $commentVotes = $commentVoteRepository->findBy(['user' => $currentUser]);
        $commentVotesMap = [];
        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

        $limitedComments = [];

        foreach ($notesWithMentionedUser as $item) {
            $note = $item['note'];
            $comments = $note->getComments()->toArray();

            usort($comments, function ($a, $b) {
                return $b->getPublicationDate() <=> $a->getPublicationDate();
            });

            $limitedComments[$note->getId()] = array_slice($comments, 0, 5);
        }

        $favoritesMap = [];
        foreach ($notes as $note) {
            $favoritesMap[$note->getId()] = $currentUser ? $currentUser->hasFavorite($note) : false;
        }

        if ($user->isBanned()) {
            $this->addFlash('danger', '<b>This user has been banned.</b> Please check the profile later.');
        }

        if ($user->getId() === 24) {
            return $this->redirectToRoute('app_profile_mentionable');
        } else {
            return $this->render('profile/index.html.twig', [
                'user' => $user,
                'notesWithMentionedUser' => $notesWithMentionedUser,
                'friendRequests' => $friendRequests,
                'notifications' => $notifications,
                'interests' => $interests,
                'noteVotes' => $noteVotes,
                'commentVotesMap' => $commentVotesMap,
                'votesMap' => $votesMap,
                'rings' => $rings,
                'ringsMobileDisplay' => $ringsMobileDisplay,
                'roles' => $rolesMap,
                'limitedComments' => $limitedComments,
                'favoritesMap' => $favoritesMap,
            ]);
        }
    }

    #[Route('/profile/mentionable/', name: 'app_profile_mentionable')]
    public function newsPage(
        FriendRequestRepository $friendRequestRepository,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        UserRepository $userRepository,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
    ): Response
    {
        $page = $userRepository->findOneBy(['id' => 24]);
        $currentUser = $this->getUser();

        if (!$page) {
            throw $this->createAccessDeniedException();
        }

        $noteVotes = $noteVoteRepository->findBy([]);
        $notifications = $notificationRepository->findBy(['receiver' => $page]);
        $notes = $em->getRepository(Note::class)->findBy(['user' => $page], ['publicationDate' => 'DESC']);
        $interests = $page->getInterests();

        $notesWithMentionedUser = [];
        foreach ($notes as $note) {
            $mentionedUser = $note->getMentionedUser();
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser,
            ];
        }

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $currentUser) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
            }
        }

        $commentVotes = $commentVoteRepository->findBy(['user' => $currentUser]);
        $commentVotesMap = [];
        foreach ($commentVotes as $vote) {
            $commentId = $vote->getComment()->getId();
            if ($vote->isUpvoted()) {
                $commentVotesMap[$commentId] = 'upvote';
            }
        }

        $favoritesMap = [];
        foreach ($notes as $note) {
            $favoritesMap[$note->getId()] = $currentUser ? $currentUser->hasFavorite($note) : false;
        }

        return $this->render('profile/mentionable.html.twig', [
            'user' => $page,
            'notesWithMentionedUser' => $notesWithMentionedUser,
            'notifications' => $notifications,
            'interests' => $interests,
            'noteVotes' => $noteVotes,
            'commentVotesMap' => $commentVotesMap,
            'votesMap' => $votesMap,
            'favoritesMap' => $favoritesMap,
        ]);
    }

    #[Route('/profile/interest/new', name: 'app_interest_new', methods: ['GET', 'POST'])]
    public function interest(Request $request,
                        EntityManagerInterface $em,
                        NotificationService $notificationService,
                        SluggerInterface $slugger,
                        TextModerationService $moderator
    ): Response
    {
        $user = $this->getUser();
        if (count($user->getInterests()) >= 5) {
            $this->addFlash('error', 'You can only have up to 5 interests.');
            return $this->redirect($request->headers->get('referer'));
        }

        if ($request->isMethod('POST')) {
            $content = $request->request->get('interest-content');

            $moderation = $moderator->analyze($content);
            $toxicityScore = $moderation['attributeScores']['TOXICITY']['summaryScore']['value'] ?? 0;
            $insultScore = $moderation['attributeScores']['INSULT']['summaryScore']['value'] ?? 0;

            if ($toxicityScore > 0.75 || $insultScore > 0.75) {
                $this->addFlash('error', 'The following interest was blocked because it may be toxic or insulting.');
                return $this->redirect($request->headers->get('referer'));
            }

            if (empty(trim($content)) || preg_match('/\s/', $content)) {
                $this->addFlash('error', 'Interest cannot contain spaces.');
                return $this->redirect($request->headers->get('referer'));
            } else {
                $interest = new Interest();
                $interest->setUser($this->getUser());
                $interest->setTitle($content);

                $em->persist($interest);
                $em->flush();
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/profile/interest/delete/{id}', name: 'app_interest_delete', methods: ['DELETE'])]
    public function deleteInterest(Request $request,
                             EntityManagerInterface $em,
                             NotificationService $notificationService,
                             SluggerInterface $slugger,
                             InterestRepository $interestRepository,
                             int $id
    ): JsonResponse
    {
        try {
            $user = $this->getUser();
            $interest = $interestRepository->findOneBy(['id' => $id, 'user' => $user]);

            if (!$interest) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Interest not found.'
                ], 404);
            }

            $em->remove($interest);
            $em->flush();

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}