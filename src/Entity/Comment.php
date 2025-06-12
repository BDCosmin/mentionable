<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @property ArrayCollection $commentVotes
 */
#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message = null;

    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Note $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'comment')]
    private Collection $notifications;

    #[ORM\Column]
    private ?\DateTime $publicationDate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isEdited = null;

    /**
     * @var Collection<int, CommentReport>
     */
    #[ORM\OneToMany(targetEntity: CommentReport::class, mappedBy: 'comment', cascade: ['remove'], orphanRemoval: true)]
    private Collection $commentReports;

    #[ORM\Column]
    private ?int $upVote = null;

    /**
     * @var Collection<int, CommentVote>
     */
    #[ORM\OneToMany(targetEntity: CommentVote::class, mappedBy: 'comment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $commentVotes;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
        $this->commentReports = new ArrayCollection();
        $this->commentVotes = new ArrayCollection();
        $this->upVote = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setComment($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getComment() === $this) {
                $notification->setComment(null);
            }
        }

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

    public function getHumanTimeComment(): string
    {
        $publishedDate = $this->getPublicationDate();
        $now = new \DateTime();
        $interval = $publishedDate->diff($now);

        if ($interval->d === 0 && $interval->h === 0 && $interval->i < 60) {
            return $interval->i === 0 ? 'just now' : $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d === 0 && $interval->h < 24) {
            return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } else {
            return $publishedDate->format('M d');
        }
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

    /**
     * @return Collection<int, CommentReport>
     */
    public function getCommentReports(): Collection
    {
        return $this->commentReports;
    }

    public function addCommentReport(CommentReport $commentReport): static
    {
        if (!$this->commentReports->contains($commentReport)) {
            $this->commentReports->add($commentReport);
            $commentReport->setComment($this);
        }

        return $this;
    }

    public function removeCommentReport(CommentReport $commentReport): static
    {
        if ($this->commentReports->removeElement($commentReport)) {
            // set the owning side to null (unless already changed)
            if ($commentReport->getComment() === $this) {
                $commentReport->setComment(null);
            }
        }

        return $this;
    }

    public function getUpVote(): ?int
    {
        return $this->upVote;
    }

    public function setUpVote(int $upVote): static
    {
        $this->upVote = $upVote;

        return $this;
    }

    public function incrementUpVote(): static
    {
        $this->upVote++;
        return $this;
    }

    public function decrementUpVote(): static
    {
        if ($this->upVote > 0) {
            $this->upVote--;
        }
        return $this;
    }

    /**
     * @return Collection<int, CommentVote>
     */
    public function getCommentVotes(): Collection
    {
        return $this->commentVotes;
    }

    public function addCommentVotes(CommentVote $commentVotes): static
    {
        if (!$this->commentVotes->contains($commentVotes)) {
            $this->commentVotes->add($commentVotes);
            $commentVotes->setComment($this);
        }

        return $this;
    }

    public function removeCommentVotes(CommentVote $commentVotes): static
    {
        if ($this->commentVotes->removeElement($commentVotes)) {
            // set the owning side to null (unless already changed)
            if ($commentVotes->getComment() === $this) {
                $commentVotes->setComment(null);
            }
        }

        return $this;
    }
}
