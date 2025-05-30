<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
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
        $notification->setNotifiedDate(new \DateTime());

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

}