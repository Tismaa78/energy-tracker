<?php

namespace App\Entity;

use App\Repository\ObjectifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

#[ORM\Entity(repositoryClass: ObjectifRepository::class)]
class Objectif
{
    public const PERIODE_MENSUEL = 'mensuel';
    public const PERIODE_HEBDO = 'hebdo';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[NotBlank(message: 'La valeur cible est obligatoire.')]
    #[PositiveOrZero(message: 'La valeur cible ne peut pas être négative.')]
    private ?float $valeurCible = null;

    #[ORM\Column(length: 10)]
    #[NotBlank]
    #[Choice(choices: [self::PERIODE_MENSUEL, self::PERIODE_HEBDO])]
    private ?string $periode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[GreaterThanOrEqual(propertyPath: 'dateDebut', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private bool $atteint = false;

    #[ORM\ManyToOne(inversedBy: 'objectifs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'objectifs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TypeEnergie $typeEnergie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValeurCible(): ?float
    {
        return $this->valeurCible;
    }

    public function setValeurCible(float $valeurCible): static
    {
        $this->valeurCible = $valeurCible;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function isAtteint(): bool
    {
        return $this->atteint;
    }

    public function setAtteint(bool $atteint): static
    {
        $this->atteint = $atteint;

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
