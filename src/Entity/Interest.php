<?php

namespace App\Entity;

use App\Repository\InterestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterestRepository::class)]
class Interest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'interests')]
    private ?User $user = null;

    /**
     * @var Collection<int, Ring>
     */
    #[ORM\OneToMany(targetEntity: Ring::class, mappedBy: 'interest')]
    private Collection $rings;

    public function __construct()
    {
        $this->rings = new ArrayCollection();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Interest';
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
            $ring->setInterest($this);
        }

        return $this;
    }

    public function removeRing(Ring $ring): static
    {
        if ($this->rings->removeElement($ring)) {
            // set the owning side to null (unless already changed)
            if ($ring->getInterest() === $this) {
                $ring->setInterest(null);
            }
        }

        return $this;
    }

}
