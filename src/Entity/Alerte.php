<?php

namespace App\Entity;

use App\Repository\AlerteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: AlerteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Alerte
{
    public const TYPE_SEUIL_DEPASSE = 'seuil dépassé';
    public const TYPE_ANOMALIE = 'anomalie';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[NotBlank]
    private ?string $typeAlerte = null;

    #[ORM\Column(type: Types::TEXT)]
    #[NotBlank]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAlerte = null;

    #[ORM\Column(nullable: true)]
    private ?float $seuilDeclenche = null;

    #[ORM\Column]
    private bool $estLue = false;

    #[ORM\ManyToOne(inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consommation $consommation = null;

    #[ORM\ManyToOne(inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TypeEnergie $typeEnergie = null;

    #[ORM\PrePersist]
    public function setDateAlerteValue(): void
    {
        if ($this->dateAlerte === null) {
            $this->dateAlerte = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeAlerte(): ?string
    {
        return $this->typeAlerte;
    }

    public function setTypeAlerte(string $typeAlerte): static
    {
        $this->typeAlerte = $typeAlerte;

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

    public function getDateAlerte(): ?\DateTimeImmutable
    {
        return $this->dateAlerte;
    }

    public function setDateAlerte(?\DateTimeImmutable $dateAlerte): static
    {
        $this->dateAlerte = $dateAlerte;

        return $this;
    }

    public function getSeuilDeclenche(): ?float
    {
        return $this->seuilDeclenche;
    }

    public function setSeuilDeclenche(?float $seuilDeclenche): static
    {
        $this->seuilDeclenche = $seuilDeclenche;

        return $this;
    }

    public function isEstLue(): bool
    {
        return $this->estLue;
    }

    public function setEstLue(bool $estLue): static
    {
        $this->estLue = $estLue;

        return $this;
    }

    public function getConsommation(): ?Consommation
    {
        return $this->consommation;
    }

    public function setConsommation(?Consommation $consommation): static
    {
        $this->consommation = $consommation;

        return $this;
    }

    public function getTypeEnergie(): ?TypeEnergie
    {
        return $this->typeEnergie;
    }

    public function setTypeEnergie(?TypeEnergie $typeEnergie): static
    {
        $this->typeEnergie = $typeEnergie;

        return $this;
    }
}
