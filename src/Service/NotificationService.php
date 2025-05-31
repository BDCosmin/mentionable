<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, Security $security, NotificationRepository $notificationRepository)
    {
        $this->em = $em;
        $this->security = $security;
        $this->notificationRepository = $notificationRepository;
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

    public function notifyComment(?User $sender, ?User $receiver, Comment $comment): void
    {
        if (!$sender instanceof User || !$receiver instanceof User) {
            throw new \LogicException('Sender and receiver must be instances of App\Entity\User.');
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

    public function getLatestUserNotifications(int $limit = 3): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        return $this->em->getRepository(Notification::class)->findBy(
            ['receiver' => $user],
            ['notifiedDate' => 'DESC'],
            $limit
        );
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

}