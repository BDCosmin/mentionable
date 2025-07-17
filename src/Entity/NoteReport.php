<?php

namespace App\Entity;

use App\Repository\NoteReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteReportRepository::class)]
class NoteReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'noteReports')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Note $note = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $type = '';

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTime $creationDate = null;

    public function getHumanTimePostReport(): string
    {
        $creationDate = $this->getCreationDate();
        $now = new \DateTime();
        $interval = $creationDate->diff($now);

        if ($interval->d === 0 && $interval->h === 0 && $interval->i < 60) {
            return $interval->i === 0 ? 'now' : $interval->i . 'min' . ($interval->i > 1 ? 's' : '') . ' ago';
        } elseif ($interval->d === 0 && $interval->h < 24) {
            return $interval->h . 'h' . ' ago';
        } else {
            return $creationDate->format('M j');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

}
