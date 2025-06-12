<?php

namespace App\Entity;

use App\Repository\FriendRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents a friend request between two users.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: FriendRequestRepository::class)]
#[UniqueEntity(fields: ['sender', 'receiver'], message: 'You have already sent a friend request to this user.')]
class FriendRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sentFriendRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedFriendRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $receiver = null;

    public function __construct()
    {
        // Nu este nevoie de inițializări, deoarece nu mai avem câmpuri suplimentare
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(User $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(User $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }
}