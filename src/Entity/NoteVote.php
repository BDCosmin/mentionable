<?php

namespace App\Entity;

use App\Repository\NoteVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteVoteRepository::class)]
class NoteVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isUpvoted = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDownvoted = null;

    #[ORM\ManyToOne(inversedBy: 'noteVotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'noteVotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Note $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isUpvoted(): ?bool
    {
        return $this->isUpvoted;
    }

    public function setIsUpvoted(?bool $isUpvoted): static
    {
        $this->isUpvoted = $isUpvoted;

        return $this;
    }

    public function isDownvoted(): ?bool
    {
        return $this->isDownvoted;
    }

    public function setIsDownvoted(?bool $isDownvoted): static
    {
        $this->isDownvoted = $isDownvoted;

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

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note): static
    {
        $this->note = $note;

        return $this;
    }
}
