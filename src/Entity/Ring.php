<?php

namespace App\Entity;

use App\Repository\RingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RingRepository::class)]
class Ring
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $banner = null;

    #[ORM\ManyToOne(inversedBy: 'rings')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    /**
     * @var Collection<int, RingMembers>
     */
    #[ORM\OneToMany(targetEntity: RingMembers::class, mappedBy: 'ring', orphanRemoval: true)]
    private Collection $ringMembers;

    #[ORM\ManyToOne(inversedBy: 'rings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Interest $interest = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'ring')]
    private Collection $notes;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'ring')]
    private Collection $notifications;

    public function __construct()
    {
        $this->ringMembers = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): static
    {
        $this->banner = $banner;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, RingMembers>
     */
    public function getRingMembers(): Collection
    {
        return $this->ringMembers;
    }

    public function addRingMember(RingMembers $ringMember): static
    {
        if (!$this->ringMembers->contains($ringMember)) {
            $this->ringMembers->add($ringMember);
            $ringMember->setRing($this);
        }

        return $this;
    }

    public function removeRingMember(RingMembers $ringMember): static
    {
        if ($this->ringMembers->removeElement($ringMember)) {
            // set the owning side to null (unless already changed)
            if ($ringMember->getRing() === $this) {
                $ringMember->setRing(null);
            }
        }

        return $this;
    }

    public function getInterest(): ?Interest
    {
        return $this->interest;
    }

    public function setInterest(?Interest $interest): static
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setRing($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getRing() === $this) {
                $note->setRing(null);
            }
        }

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
            $notification->setRing($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getRing() === $this) {
                $notification->setRing(null);
            }
        }

        return $this;
    }
}
