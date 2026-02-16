<?php

namespace App\Entity;

use App\Repository\TypeEnergieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

#[ORM\Entity(repositoryClass: TypeEnergieRepository::class)]
class TypeEnergie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[NotBlank]
    private ?string $libelle = null;

    #[ORM\Column(length: 20)]
    #[NotBlank]
    private ?string $unite = null;

    #[ORM\Column(nullable: true)]
    #[PositiveOrZero]
    private ?float $tarifUnitaire = null;

    #[ORM\ManyToOne(inversedBy: 'typeEnergies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Consommation>
     */
    #[ORM\OneToMany(targetEntity: Consommation::class, mappedBy: 'typeEnergie')]
    private Collection $consommations;

    /**
     * @var Collection<int, Objectif>
     */
    #[ORM\OneToMany(targetEntity: Objectif::class, mappedBy: 'typeEnergie')]
    private Collection $objectifs;

    /**
     * @var Collection<int, Alerte>
     */
    #[ORM\OneToMany(targetEntity: Alerte::class, mappedBy: 'typeEnergie')]
    private Collection $alertes;

    public function __construct()
    {
        $this->consommations = new ArrayCollection();
        $this->objectifs = new ArrayCollection();
        $this->alertes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    public function getTarifUnitaire(): ?float
    {
        return $this->tarifUnitaire;
    }

    public function setTarifUnitaire(?float $tarifUnitaire): static
    {
        $this->tarifUnitaire = $tarifUnitaire;

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
     * @return Collection<int, Consommation>
     */
    public function getConsommations(): Collection
    {
        return $this->consommations;
    }

    public function addConsommation(Consommation $consommation): static
    {
        if (!$this->consommations->contains($consommation)) {
            $this->consommations->add($consommation);
            $consommation->setTypeEnergie($this);
        }

        return $this;
    }

    public function removeConsommation(Consommation $consommation): static
    {
        if ($this->consommations->removeElement($consommation)) {
            if ($consommation->getTypeEnergie() === $this) {
                $consommation->setTypeEnergie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Objectif>
     */
    public function getObjectifs(): Collection
    {
        return $this->objectifs;
    }

    public function addObjectif(Objectif $objectif): static
    {
        if (!$this->objectifs->contains($objectif)) {
            $this->objectifs->add($objectif);
            $objectif->setTypeEnergie($this);
        }

        return $this;
    }

    public function removeObjectif(Objectif $objectif): static
    {
        if ($this->objectifs->removeElement($objectif)) {
            if ($objectif->getTypeEnergie() === $this) {
                $objectif->setTypeEnergie(null);
            }
        }

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
            $alerte->setTypeEnergie($this);
        }

        return $this;
    }

    public function removeAlerte(Alerte $alerte): static
    {
        if ($this->alertes->removeElement($alerte)) {
            if ($alerte->getTypeEnergie() === $this) {
                $alerte->setTypeEnergie(null);
            }
        }

        return $this;
    }
}
