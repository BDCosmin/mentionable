<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $upVote = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $downVote = 0;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'note', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $comments;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nametag = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'note', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\Column]
    private ?\DateTime $publicationDate = null;

    /**
     * @var Collection<int, NoteVote>
     */
    #[ORM\OneToMany(targetEntity: NoteVote::class, mappedBy: 'note')]
    private Collection $noteVotes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isEdited = null;

    /**
     * @var Collection<int, NoteReport>
     */
    #[ORM\OneToMany(targetEntity: NoteReport::class, mappedBy: 'note', cascade: ['remove'], orphanRemoval: true)]
    private Collection $noteReports;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->noteVotes = new ArrayCollection();
        $this->noteReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUpVote(): ?int
    {
        return $this->upVote;
    }

    public function setUpVote(?int $upVote): static
    {
        $this->upVote = $upVote;

        return $this;
    }

    public function getDownVote(): ?int
    {
        return $this->downVote;
    }

    public function setDownVote(?int $downVote): static
    {
        $this->downVote = $downVote;

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
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setNote($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getNote() === $this) {
                $comment->setNote(null);
            }
        }

        return $this;
    }

    public function getNametag(): ?string
    {
        return $this->nametag;
    }

    public function setNametag(?string $nametag): static
    {
        $this->nametag = $nametag;

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
            $notification->setNote($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getNote() === $this) {
                $notification->setNote(null);
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

    public function incrementDownVote(): static
    {
        $this->downVote++;
        return $this;
    }

    public function decrementDownVote(): static
    {
        if ($this->downVote > 0) {
            $this->downVote--;
        }
        return $this;
    }

    public function getHumanTimePost(): string
    {
        $publishedDate = $this->getPublicationDate();
        $now = new \DateTime();
        $interval = $publishedDate->diff($now);

        if ($interval->d === 0 && $interval->h === 0 && $interval->i < 60) {
            return $interval->i === 0 ? 'now' : $interval->i . ' min' . ($interval->i > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d === 0 && $interval->h < 24) {
            return $interval->h . 'h' . ' ago';
        } else {
            return $publishedDate->format('M d');
        }
    }

    /**
     * @return Collection<int, NoteVote>
     */
    public function getNoteVotes(): Collection
    {
        return $this->noteVotes;
    }

    public function addNoteVote(NoteVote $noteVote): static
    {
        if (!$this->noteVotes->contains($noteVote)) {
            $this->noteVotes->add($noteVote);
            $noteVote->setNote($this);
        }

        return $this;
    }

    public function removeNoteVote(NoteVote $noteVote): static
    {
        if ($this->noteVotes->removeElement($noteVote)) {
            // set the owning side to null (unless already changed)
            if ($noteVote->getNote() === $this) {
                $noteVote->setNote(null);
            }
        }

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
     * @return Collection<int, NoteReport>
     */
    public function getNoteReports(): Collection
    {
        return $this->noteReports;
    }

    public function addNoteReport(NoteReport $noteReport): static
    {
        if (!$this->noteReports->contains($noteReport)) {
            $this->noteReports->add($noteReport);
            $noteReport->setNote($this);
        }

        return $this;
    }

    public function removeNoteReport(NoteReport $noteReport): static
    {
        if ($this->noteReports->removeElement($noteReport)) {
            // set the owning side to null (unless already changed)
            if ($noteReport->getNote() === $this) {
                $noteReport->setNote(null);
            }
        }

        return $this;
    }

}
