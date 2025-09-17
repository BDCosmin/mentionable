<?php

namespace App\Entity;

use App\Repository\CommentReplyReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentReplyReportRepository::class)]
class CommentReplyReport
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommentReply $reply = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending';

    #[ORM\Column]
    private ?\DateTime $creationDate = null;

    #[ORM\Column]
    private ?int $reporterId = null;

    public function getId(): ?int { return $this->id; }
    public function getReply(): ?CommentReply { return $this->reply; }
    public function setReply(?CommentReply $reply): static { $this->reply = $reply; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCreationDate(): ?\DateTime { return $this->creationDate; }
    public function setCreationDate(\DateTime $date): static { $this->creationDate = $date; return $this; }
    public function getReporterId(): ?int { return $this->reporterId; }
    public function setReporterId(int $id): static { $this->reporterId = $id; return $this; }
}
