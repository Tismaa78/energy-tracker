<?php

namespace App\Entity;

use App\Repository\ConsommationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

#[ORM\Entity(repositoryClass: ConsommationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Consommation
{
    public const SOURCE_MANUEL = 'manuel';
    public const SOURCE_IMPORT = 'import';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $periodeDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[GreaterThanOrEqual(propertyPath: 'periodeDebut', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    private ?\DateTimeInterface $periodeFin = null;

    #[ORM\Column]
    #[NotBlank(message: 'La valeur est obligatoire.')]
    #[PositiveOrZero(message: 'La valeur ne peut pas être négative.')]
    private ?float $valeur = null;

    /**
     * Coût estimé (valeur * tarif_unitaire du TypeEnergie). Column "cout" en BDD pour compatibilité.
     */
    #[ORM\Column(name: 'cout', nullable: true)]
    private ?float $coutEstime = null;

    #[ORM\Column(length: 20)]
    #[Choice(choices: [self::SOURCE_MANUEL, self::SOURCE_IMPORT])]
    private ?string $sourceSaisie = self::SOURCE_MANUEL;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'consommations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'consommations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Logement $logement = null;

    #[ORM\ManyToOne(inversedBy: 'consommations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TypeEnergie $typeEnergie = null;

    /**
     * @var Collection<int, Alerte>
     */
    #[ORM\OneToMany(targetEntity: Alerte::class, mappedBy: 'consommation', cascade: ['remove'])]
    private Collection $alertes;

    public function __construct()
    {
        $this->alertes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodeDebut(): ?\DateTimeInterface
    {
        return $this->periodeDebut;
    }

    public function setPeriodeDebut(?\DateTimeInterface $periodeDebut): static
    {
        $this->periodeDebut = $periodeDebut;

        return $this;
    }

    public function getPeriodeFin(): ?\DateTimeInterface
    {
        return $this->periodeFin;
    }

    public function setPeriodeFin(?\DateTimeInterface $periodeFin): static
    {
        $this->periodeFin = $periodeFin;

        return $this;
    }

    public function getValeur(): ?float
    {
        return $this->valeur;
    }

    public function setValeur(float $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getCoutEstime(): ?float
    {
        return $this->coutEstime;
    }

    public function setCoutEstime(?float $coutEstime): static
    {
        $this->coutEstime = $coutEstime;

        return $this;
    }

    public function getSourceSaisie(): ?string
    {
        return $this->sourceSaisie;
    }

    public function setSourceSaisie(string $sourceSaisie): static
    {
        $this->sourceSaisie = $sourceSaisie;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): static
    {
        $this->logement = $logement;

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

    /**
     * @return Collection<int, Alerte>
     */
    public function getAlertes(): Collection
    {
        return $this->alertes;
    }

    public function addAlerte(Alerte $alerte): static
    {
        if (!$this->alertes->contains($alerte)) {
            $this->alertes->add($alerte);
            $alerte->setConsommation($this);
        }

        return $this;
    }

    public function removeAlerte(Alerte $alerte): static
    {
        if ($this->alertes->removeElement($alerte)) {
            if ($alerte->getConsommation() === $this) {
                $alerte->setConsommation(null);
            }
        }

        return $this;
    }
}
