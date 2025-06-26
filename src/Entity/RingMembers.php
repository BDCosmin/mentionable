<?php

namespace App\Entity;

use App\Repository\RingMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RingMemberRepository::class)]
class RingMembers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ringMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ring $ring = null;

    #[ORM\ManyToOne(inversedBy: 'ringMembers')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $joinedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRing(): ?Ring
    {
        return $this->ring;
    }

    public function setRing(?Ring $ring): static
    {
        $this->ring = $ring;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getJoinedAt(): ?\DateTime
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTime $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }
}
