<?php

namespace App\Entity;

use App\Repository\CommentReplyVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentReplyVoteRepository::class)]
class CommentReplyVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommentReply $reply = null;

    #[ORM\Column]
    private ?bool $isUpvoted = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReply(): ?CommentReply
    {
        return $this->reply;
    }

    public function setReply(?CommentReply $reply): static
    {
        $this->reply = $reply;
        return $this;
    }

    public function isUpvoted(): ?bool
    {
        return $this->isUpvoted;
    }

    public function setIsUpvoted(bool $isUpvoted): static
    {
        $this->isUpvoted = $isUpvoted;
        return $this;
    }
}
