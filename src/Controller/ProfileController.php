<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\FriendRequest;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Repository\FriendRequestRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
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

    #[Route('/friends/add', name: 'app_profile_add_friend_by_nametag', methods: ['POST'])]
    public function addFriendByNametag(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager,NotificationService $notificationService): Response
    {
        $nametag = $request->request->get('friend_nametag');
        $friend = $userRepository->findOneBy(['nametag' => $nametag]);

        if (!$friend) {
            $this->addFlash('error', 'User with this nametag does not exist.');
            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();
        if ($user->getId() === $friend->getId()) {
            $this->addFlash('error', 'You cannot add yourself as a friend.');
            return $this->redirectToRoute('app_profile');
        }

        if ($user->getFriends()->contains($friend)) {
            $this->addFlash('error', 'You are already friends.');
            return $this->redirectToRoute('app_profile');
        }

        $existingRequest = $entityManager->getRepository(FriendRequest::class)->findOneBy([
            'sender' => $user,
            'receiver' => $friend,
        ]);

        if ($existingRequest) {
            $this->addFlash('error', 'Friend request already sent to this user.');
            return $this->redirectToRoute('app_profile');
        }

        $incomingRequest = $entityManager->getRepository(FriendRequest::class)->findOneBy([
            'sender' => $friend,
            'receiver' => $user,
        ]);

        if ($incomingRequest) {
            $this->addFlash('error', 'This user has already sent you a friend request.');
            return $this->redirectToRoute('app_profile');
        }

        // Create new friend request
        $friendRequest = new FriendRequest();
        $friendRequest->setSender($user);
        $friendRequest->setReceiver($friend);

        $message = sprintf('%s sent you a friend request from',$user->getNametag());
        $link = '/profile';

        $notificationService->createNotification(
            $user,
            $friend,
            $friendRequest,
            $message,
            $link
        );


        $entityManager->persist($friendRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Friend request sent successfully!');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/accept/{id}', name: 'app_profile_accept_friend')]
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

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/reject/{id}', name: 'app_profile_reject_friend')]
    public function rejectFriend(FriendRequest $friendRequest, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
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

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/friends/remove/{id}', name: 'app_profile_remove_friend')]
    public function removeFriend(User $friend, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $user->removeFriend($friend);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Friend removed successfully!');

        return $this->redirectToRoute('app_profile');
    }
}