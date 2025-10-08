<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $creationDate = null;

    #[ORM\Column(length: 255)]
    private ?string $nametag = null;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\Column(length: 255)]
    private ?string $avatar = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'mentionedUser')]
    private Collection $mentionedNotes;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'sender')]
    private Collection $senderNotifications;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'receiver')]
    private Collection $receiverNotifications;


    /**
     * @var Collection<int, NoteVote>
     */
    #[ORM\OneToMany(targetEntity: NoteVote::class, mappedBy: 'user')]
    private Collection $noteVotes;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'friendsWithMe')]
    #[ORM\JoinTable(name: 'user_friends')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'friend_id', referencedColumnName: 'id')]
    private Collection $friends;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'friends')]
    private Collection $friendsWithMe;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'sender', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sentFriendRequests;

    /**
     * @var Collection<int, FriendRequest>
     */
    #[ORM\OneToMany(targetEntity: FriendRequest::class, mappedBy: 'receiver', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $receivedFriendRequests;

    /**
     * @var Collection<int, Interest>
     */
    #[ORM\OneToMany(targetEntity: Interest::class, mappedBy: 'user')]
    private Collection $interests;

    /**
     * @var Collection<int, Ring>
     */
    #[ORM\OneToMany(targetEntity: Ring::class, mappedBy: 'user')]
    private Collection $rings;

    /**
     * @var Collection<int, RingMembers>
     */
    #[ORM\OneToMany(targetEntity: RingMembers::class, mappedBy: 'user')]
    private Collection $ringMembers;

    #[ORM\Column(type: 'boolean')]
    private bool $isBanned = false;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $tickets;

    #[ORM\ManyToMany(targetEntity: Note::class)]
    #[ORM\JoinTable(name: 'user_favorite_notes')]
    private Collection $favoriteNotes;

    /**
     * @var Collection<int, CommentReply>
     */
    #[ORM\OneToMany(targetEntity: CommentReply::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $commentReplies;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->senderNotifications = new ArrayCollection();
        $this->receiverNotifications = new ArrayCollection();
        $this->noteVotes = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->friendsWithMe = new ArrayCollection();
        $this->sentFriendRequests = new ArrayCollection();
        $this->receivedFriendRequests = new ArrayCollection();
        $this->interests = new ArrayCollection();
        $this->rings = new ArrayCollection();
        $this->ringMembers = new ArrayCollection();
        $this->mentionedNotes = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->favoriteNotes = new ArrayCollection();
        $this->commentReplies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getNametag(): ?string
    {
        return $this->nametag;
    }

    public function setNametag(string $nametag): static
    {
        $this->nametag = $nametag;

        return $this;
    }

    public function getMentionedNotes(): Collection
    {
        return $this->mentionedNotes;
    }

    public function addMentionedNote(Note $note): static
    {
        if (!$this->mentionedNotes->contains($note)) {
            $this->mentionedNotes->add($note);
            $note->setMentionedUser($this);
        }
        return $this;
    }

    public function removeMentionedNote(Note $note): static
    {
        if ($this->mentionedNotes->removeElement($note)) {
            if ($note->getMentionedUser() === $this) {
                $note->setMentionedUser(null);
            }
        }
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): static
    {
        $this->avatar = $avatar;

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
            $note->setUser($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }

        return $this;
    }

    public function getSenderNotifications(): Collection
    {
        return $this->senderNotifications;
    }

    public function setSenderNotifications(Collection $senderNotifications): void
    {
        $this->senderNotifications = $senderNotifications;
    }

    public function getReceiverNotifications(): Collection
    {
        return $this->receiverNotifications;
    }

    public function setReceiverNotifications(Collection $receiverNotifications): void
    {
        $this->receiverNotifications = $receiverNotifications;
    }

    /**
     * @return Collection<int, NoteVote>
     */
    public function getNoteVotes(): Collection
    {
        return $this->noteVotes;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(User $friend): static
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            if (!$friend->getFriends()->contains($this)) {
                $friend->addFriend($this); // simetrie reală
            }
        }

        return $this;
    }

    public function removeFriend(User $friend): static
    {
        if ($this->friends->contains($friend)) {
            $this->friends->removeElement($friend);
            if ($friend->getFriends()->contains($this)) {
                $friend->removeFriend($this); // elimină și din partea cealaltă
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFriendsWithMe(): Collection
    {
        return $this->friendsWithMe;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendRequests;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getReceivedFriendRequests(): Collection
    {
        return $this->receivedFriendRequests;
    }

    /**
     * @return Collection<int, Interest>
     */
    public function getInterests(): Collection
    {
        return $this->interests;
    }

    public function addInterest(Interest $interest): static
    {
        if (!$this->interests->contains($interest)) {
            $this->interests->add($interest);
            $interest->setUser($this);
        }

        return $this;
    }

    public function removeInterest(Interest $interest): static
    {
        if ($this->interests->removeElement($interest)) {
            // set the owning side to null (unless already changed)
            if ($interest->getUser() === $this) {
                $interest->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ring>
     */
    public function getRings(): Collection
    {
        return $this->rings;
    }

    public function addRing(Ring $ring): static
    {
        if (!$this->rings->contains($ring)) {
            $this->rings->add($ring);
            $ring->setUser($this);
        }

        return $this;
    }

    public function removeRing(Ring $ring): static
    {
        if ($this->rings->removeElement($ring)) {
            // set the owning side to null (unless already changed)
            if ($ring->getUser() === $this) {
                $ring->setUser(null);
            }
        }

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
            $ringMember->setUser($this);
        }

        return $this;
    }

    public function removeRingMember(RingMembers $ringMember): static
    {
        if ($this->ringMembers->removeElement($ringMember)) {
            // set the owning side to null (unless already changed)
            if ($ringMember->getUser() === $this) {
                $ringMember->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setUser($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getUser() === $this) {
                $ticket->setUser(null);
            }
        }

        return $this;
    }

    public function getFavoriteNotes(): Collection
    {
        return $this->favoriteNotes;
    }

    public function addFavorite(Note $note): static
    {
        if (!$this->favoriteNotes->contains($note)) {
            $this->favoriteNotes->add($note);
        }
        return $this;
    }

    public function removeFavorite(Note $note): static
    {
        $this->favoriteNotes->removeElement($note);
        return $this;
    }

    public function hasFavorite(Note $note): bool
    {
        return $this->favoriteNotes->contains($note);
    }

    /**
     * @return Collection<int, CommentReply>
     */
    public function getCommentReplies(): Collection
    {
        return $this->commentReplies;
    }

    public function addCommentReply(CommentReply $commentReply): static
    {
        if (!$this->commentReplies->contains($commentReply)) {
            $this->commentReplies->add($commentReply);
            $commentReply->setUser($this);
        }

        return $this;
    }

    public function removeCommentReply(CommentReply $commentReply): static
    {
        if ($this->commentReplies->removeElement($commentReply)) {
            // set the owning side to null (unless already changed)
            if ($commentReply->getUser() === $this) {
                $commentReply->setUser(null);
            }
        }

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }
}
