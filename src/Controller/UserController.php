<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\CommentVoteRepository;
use App\Repository\FriendRequestRepository;
use App\Repository\NoteRepository;
use App\Repository\NoteVoteRepository;
use App\Repository\NotificationRepository;
use App\Repository\RingMemberRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(FriendRequestRepository $friendRequestRepository, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);
        $notifications = $notificationRepository->findBy(['receiver' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'friendRequests' => $friendRequests,
            'notifications' => $notifications,
        ]);
    }

    #[Route('/my-notes/user', name: 'app_user_notes')]
    public function myNotes(
        NoteRepository $noteRepository,
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        FriendRequestRepository $friendRequestRepository,
        NotificationService $notificationService,
        EntityManagerInterface $em,
        RingMemberRepository $ringMemberRepository,
    ): Response {
        $user = $this->getUser();

        $notes = $noteRepository->findBy(['user' => $user], ['publicationDate' => 'DESC']);
        $noteVotes = $noteVoteRepository->findBy([]);

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

        $notesWithMentionedUser = [];
        foreach ($notes as $note) {
            $mentionedUser = $note->getMentionedUser(); // Fetch the User object
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser, // Pass the User object
            ];
        }

        $notifications = $notificationService->getLatestUserNotifications();
        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);

        $notesCount = count($notes);

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
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

        $limitedComments = [];
        foreach ($notes as $note) {
            $comments = $note->getComments()->slice(0, 5);
            $limitedComments[$note->getId()] = $comments;
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'notesCount' => $notesCount,
            'notes' => $notes,
            'notifications' => $notifications,
            'friendRequests' => $friendRequests,
            'noteVotes' => $noteVotes,
            'commentVotesMap' => $commentVotesMap,
            'votesMap' => $votesMap,
            'notesWithMentionedUser' => $notesWithMentionedUser,
            'rolesMap' => $rolesMap,
            'limitedComments' => $limitedComments,
        ]);
    }

    #[Route('/my-favorites', name: 'app_user_favorites')]
    public function myFavorites(
        NoteVoteRepository $noteVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        FriendRequestRepository $friendRequestRepository,
        NotificationService $notificationService,
        EntityManagerInterface $em,
        RingMemberRepository $ringMemberRepository
    ): Response {
        $user = $this->getUser();
        $notes = $user->getFavoriteNotes();

        $noteVotes = $noteVoteRepository->findBy([]);

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

        $notesWithMentionedUser = [];
        foreach ($notes as $note) {
            $mentionedUser = $note->getMentionedUser();
            $notesWithMentionedUser[] = [
                'note' => $note,
                'mentionedUser' => $mentionedUser,
            ];
        }

        $notifications = $notificationService->getLatestUserNotifications();
        $friendRequests = $friendRequestRepository->findBy(['receiver' => $user]);

        $notesCount = count($notes);

        $votesMap = [];
        foreach ($noteVotes as $vote) {
            if ($vote->getUser() === $user) {
                if ($vote->isUpvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'upvote';
                } elseif ($vote->isDownvoted()) {
                    $votesMap[$vote->getNote()->getId()] = 'downvote';
                }
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

        $limitedComments = [];
        foreach ($notes as $note) {
            $comments = $note->getComments()->slice(0, 5);
            $limitedComments[$note->getId()] = $comments;
        }

        $favoritesMap = [];
        foreach ($notes as $note) {
            $favoritesMap[$note->getId()] = $user->hasFavorite($note);
        }

        return $this->render('user/favorites.html.twig', [
            'user' => $user,
            'notesCount' => $notesCount,
            'notes' => $notes,
            'notifications' => $notifications,
            'friendRequests' => $friendRequests,
            'noteVotes' => $noteVotes,
            'commentVotesMap' => $commentVotesMap,
            'votesMap' => $votesMap,
            'notesWithMentionedUser' => $notesWithMentionedUser,
            'rolesMap' => $rolesMap,
            'limitedComments' => $limitedComments,
            'favoritesMap' => $favoritesMap,
        ]);
    }

    #[Route('/user/{id}/clear-favorites', name: 'app_user_clear_favorites')]
    public function clearFavorites(User $user, EntityManagerInterface $em): Response
    {
        $favorites = $user->getFavoriteNotes();
        foreach ($favorites as $favorite) {
            $user->removeFavorite($favorite);
        }
        $em->flush();

        $this->addFlash('success', 'All favorites notes cleared!');

        return $this->redirectToRoute('app_user_favorites');
    }

    #[Route('/my-notifications/user', name: 'app_user_notifications')]
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

    #[Route('/friends/{id}/view/', name: 'app_profile_view_friends')]
    public function viewFriends(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager,NotificationService $notificationService): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $notificationService->getLatestUserNotifications();

        return $this->render('profile/friends.html.twig',[
            'notifications' => $notifications,
            'user' => $user,
        ]);
    }

    #[Route('/friends/add/user/{id}', name: 'app_profile_add_friend', methods: ['POST'])]
    public function addFriend(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager,NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $friend = null;

        if ($request->request->has('friend_id')) {
            $friend = $userRepository->find($request->request->get('friend_id'));
        } elseif ($request->request->has('friend_nametag')) {
            $friend = $userRepository->findOneBy(['nametag' => $request->request->get('friend_nametag')]);
        }

        if (!$friend) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);
        }

        if ($user->getId() === $friend->getId()) {
            $this->addFlash('error', 'You cannot add yourself as a friend.');
            return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
        }

        if ($user->getFriends()->contains($friend)) {
            $this->addFlash('error', 'You are already friends.');
            return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);
        }

        $existingRequest = $entityManager->getRepository(FriendRequest::class)->findOneBy([
            'sender' => $user,
            'receiver' => $friend,
        ]);

        // Check if it is for 'mentionable' page or not
        if ($friend->getId() != 24) {
            if ($existingRequest) {
                $this->addFlash('error', 'Friend request already sent to this user.');
                return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);
            }

            $friendRequest = new FriendRequest();
            $friendRequest->setSender($user);
            $friendRequest->setReceiver($friend);

            $message = sprintf('%s sent you a friend request from', $user->getNametag());
            $link = '/profile';

            $notificationService->notifyFriendRequest(
                $user,
                $friend,
                $friendRequest,
                $message,
                $link
            );

            $entityManager->persist($friendRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Friend request sent successfully!');

            return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);
        } else {
            if ($existingRequest) {
                $this->addFlash('error', 'Page already followed.');
                return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);
            }

            $friendRequest = new FriendRequest();
            $friendRequest->setSender($user);
            $friendRequest->setReceiver($friend);

            $user->addFriend($friend);
            $friend->addFriend($user);

            $entityManager->persist($friendRequest);
            $entityManager->remove($friendRequest);

            $entityManager->flush();

            return $this->redirectToRoute('app_profile', ['id' => $friend->getId()]);

        }
    }

    #[Route('/friends/accept/user/{id}', name: 'app_profile_accept_friend')]
    public function acceptFriend(Request $request, FriendRequest $friendRequest, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $sender = $friendRequest->getSender();
        $notification = $notificationRepository->findOneBy([
            'friendRequest' => $friendRequest,
        ]);

        if ($friendRequest->getReceiver() !== $user) {
            $this->addFlash('error', 'You do not have permission to accept this request.');
            return $this->redirectToRoute('app_profile');
        }

        $user->addFriend($sender);
        $sender->addFriend($user);

        $entityManager->remove($friendRequest);

        $entityManager->persist($user);
        $entityManager->persist($sender);

        if ($notification) {
            $notification->setIsRead(true);
            $entityManager->remove($notification);
        }

        $entityManager->flush();

        $this->addFlash('success', 'The friend request has been accepted!');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/friends/reject/user/{id}', name: 'app_profile_reject_friend')]
    public function rejectFriend(Request $request, FriendRequest $friendRequest, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notification = $notificationRepository->findOneBy([
            'friendRequest' => $friendRequest,
        ]);
        if ($friendRequest->getReceiver() !== $user) {
            $this->addFlash('error', 'You do not have permission to reject this request.');
            return $this->redirectToRoute('app_profile');
        }

        $entityManager->remove($friendRequest);

        if ($notification) {
            $notification->setIsRead(true);
            $entityManager->remove($notification);
        }

        $entityManager->flush();

        $this->addFlash('danger', 'The friend request has been rejected.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/friends/remove/user/{id}', name: 'app_profile_remove_friend')]
    public function removeFriend(Request $request, User $friend, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $friendCopy = $friend;

        $user->removeFriend($friend);
        $entityManager->persist($user);
        $entityManager->flush();

        // Check if it is for 'mentionable' page or not
        if ($friendCopy->getId() != 24) {
            $this->addFlash('success', 'Friend removed successfully!');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/user/{id}/clear-all-notifications', name: 'app_user_clear_notifications', methods: ['GET','POST'])]
    public function clearAllNotifications(Request $request, NotificationService $notificationService): Response
    {
        $user = $this->getUser();

        $notificationService->clearAll($user);

        $this->addFlash('success', 'Notifications cleared successfully!');

        return $this->redirect($request->headers->get('referer'));
    }
}
