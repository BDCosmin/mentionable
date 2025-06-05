<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Note $note = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Comment $comment = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'senderNotifications')]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receiverNotifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

//    #[ORM\Column(length: 255, nullable: true)]
//    private ?string $message = null;
//
//    #[ORM\Column(length: 255, nullable: true)]
//    private ?string $link = null;

    #[ORM\Column]
    private ?\DateTime $notifiedDate = null;

    #[ORM\Column]
    private ?bool $isRead = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

//    public function getMessage(): ?string { return $this->message; }
//    public function setMessage(?string $message): static {
//        $this->message = $message;
//        return $this;
//    }
//
//    public function getLink(): ?string { return $this->link; }
//    public function setLink(?string $link): static {
//        $this->link = $link;
//        return $this;
//    }


    public function getNotifiedDate(): ?\DateTime
    {
        return $this->notifiedDate;
    }

    public function setNotifiedDate(\DateTime $notifiedDate): static
    {
        $this->notifiedDate = $notifiedDate;

        return $this;
    }

    public function getHumanTimeNotification(): string
    {
        $now = new \DateTime();
        $interval = $now->diff($this->notifiedDate);

        if ($interval->d > 0) {
            return $interval->d . 'd ago';
        } elseif ($interval->h > 0) {
            return $interval->h . 'h ago';
        } elseif ($interval->i > 0) {
            return $interval->i . 'min ago';
        }

        return 'just now';
    }

    public function isRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

}
