<?php

namespace App\Entity;

use App\Repository\CommentReplyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentReplyRepository::class)]
class CommentReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commentReplies')]
    private ?Comment $comment = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTime $publicationDate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isEdited = null;

    #[ORM\ManyToOne(inversedBy: 'commentReplies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?int $upvote = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getPublicationDate(): ?\DateTime
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTime $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function isEdited(): ?bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(?bool $isEdited): static
    {
        $this->isEdited = $isEdited;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getUpvote(): ?int
    {
        return $this->upvote;
    }

    public function setUpvote(?int $upvote): static
    {
        $this->upvote = $upvote;

        return $this;
    }

    public function getHumanTimeReplyComment(): string
    {
        $interval = $this->getPublicationDate()->diff(new \DateTime());

        if ($interval->d === 0 && $interval->h === 0 && $interval->i < 60) {
            return $interval->i === 0 ? 'now' : $interval->i . 'min' . ($interval->i > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d === 0 && $interval->h < 24) {
            return $interval->h . 'h' . ' ago';
        } else {
            return $this->getPublicationDate()->format('M j');
        }
    }
}
