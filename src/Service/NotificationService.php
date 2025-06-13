<?php

namespace App\Service;

use App\Entity\FriendRequest;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\FriendRequestRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @property NotificationRepository $notificationRepository
 * @property FriendRequestRepository $friendRequestRepository
 * @property Security $security
 */
class NotificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, Security $security, NotificationRepository $notificationRepository, FriendRequestRepository $friendRequestRepository)
    {
        $this->em = $em;
        $this->security = $security;
        $this->notificationRepository = $notificationRepository;
        $this->friendRequestRepository = $friendRequestRepository;
    }

    public function notifyNote(?User $sender, ?User $receiver, Note $note): void
    {
        if (!$sender instanceof User || !$receiver instanceof User) {
            throw new \LogicException('Sender and receiver must be instances of App\Entity\User.');
        }

        $notification = new Notification();
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setNote($note);
        $notification->setType('mentioned');
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function createNotification(User $sender, User $receiver, FriendRequest $friendRequest, string $message, string $link): void
    {
        $notification = new Notification();
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setType('friend_request');
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);
        $notification->setFriendRequest($friendRequest);

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function notifyComment(?User $sender, ?User $receiver, Comment $comment): void
    {
        if (!$sender instanceof User || !$receiver instanceof User) {
            throw new \LogicException('Sender and receiver must be instances of App\Entity\User.');
        }

        if ($sender === $comment->getNote()->getUser()) {
            return;
        }

        $notification = new Notification();
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setComment($comment);
        $notification->setType('commented');
        $notification->setNote($comment->getNote());
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function notifyUpvote(?User $sender, ?User $receiver, Note $note): void
    {
        if (!$sender instanceof User || !$receiver instanceof User) {
            throw new \LogicException('Sender and receiver must be instances of App\Entity\User.');
        }

        if ($sender === $note->getUser()) {
            return;
        }

        $notification = new Notification();
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setType('upvoted');
        $notification->setNote($note);
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function notifyDownvote(?User $sender, ?User $receiver, Note $note): void
    {
        if (!$sender instanceof User || !$receiver instanceof User) {
            throw new \LogicException('Sender and receiver must be instances of App\Entity\User.');
        }

        if ($sender === $note->getUser()) {
            return;
        }

        $notification = new Notification();
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setType('downvoted');
        $notification->setNote($note);
        $notification->setNotifiedDate(new \DateTime());
        $notification->setIsRead(false);

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function getLatestUserNotifications(int $limit = 3): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        return $this->notificationRepository->findLatestByReceiverWithSender($user, $limit);
    }

    public function getLastUserNotification(int $limit = 1): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        return $this->notificationRepository->findLatestByReceiverWithSender($user, $limit);
    }

    public function markOneAsRead(UserInterface $user, Notification $notification): void
    {
        if ($notification->getReceiver() !== $user) {
            throw new \LogicException('You are not allowed to mark this notification.');
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->em->flush();
        }
    }

    public function markAllAsRead(UserInterface $user): void
    {
        $notifications = $this->notificationRepository->findBy([
            'receiver' => $user,
            'isRead' => false,
        ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->em->flush();
    }

    public function getFriendRequests(UserInterface $user, FriendRequestRepository $friendRequestRepository): array
    {
        if($user)
        {
            return $friendRequestRepository->findBy(['receiver' => $user]);

        } else {
            return [];
        }
    }

}