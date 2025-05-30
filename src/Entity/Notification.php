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

    #[ORM\ManyToOne(inversedBy: 'receiverNotifications')]
    private ?Note $note = null;

    #[ORM\ManyToOne(inversedBy: 'senderNotifications')]
    private ?Comment $comment = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'senderNotifications')]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receiverNotifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column]
    private ?\DateTime $notifiedDate = null;

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

    public function getNotifiedDate(): ?\DateTime
    {
        return $this->notifiedDate;
    }

    public function setNotifiedDate(\DateTime $notifiedDate): static
    {
        $this->notifiedDate = $notifiedDate;

        return $this;
    }

    public function getHumanTimeNotif(): string
    {
        $notifiedDate = $this->getNotifiedDate();
        $now = new \DateTime();
        $interval = $notifiedDate->diff($now);

        if ($interval->d === 0 && $interval->h === 0 && $interval->i < 60) {
            return $interval->i === 0 ? 'just now' : $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d === 0 && $interval->h < 24) {
            return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } else {
            return $notifiedDate->format('M d');
        }
    }

}
