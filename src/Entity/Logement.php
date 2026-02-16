<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    public const TYPE_MAISON = 'maison';
    public const TYPE_APPART = 'appart';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private ?string $adresse = null;

    #[ORM\Column(length: 20)]
    #[NotBlank]
    #[Choice(choices: [self::TYPE_MAISON, self::TYPE_APPART], message: 'Choisir maison ou appart')]
    private ?string $typeLogement = null;

    #[ORM\Column]
    #[Positive]
    private ?float $surfaceM2 = null;

    #[ORM\Column]
    #[PositiveOrZero]
    private ?int $nbOccupants = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Consommation>
     */
    #[ORM\OneToMany(targetEntity: Consommation::class, mappedBy: 'logement')]
    private Collection $consommations;

    public function __construct()
    {
        $this->consommations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTypeLogement(): ?string
    {
        return $this->typeLogement;
    }

    public function setTypeLogement(string $typeLogement): static
    {
        $this->typeLogement = $typeLogement;

        return $this;
    }

    public function getSurfaceM2(): ?float
    {
        return $this->surfaceM2;
    }

    public function setSurfaceM2(float $surfaceM2): static
    {
        $this->surfaceM2 = $surfaceM2;

        return $this;
    }

    public function getNbOccupants(): ?int
    {
        return $this->nbOccupants;
    }

    public function setNbOccupants(int $nbOccupants): static
    {
        $this->nbOccupants = $nbOccupants;

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
            $consommation->setLogement($this);
        }

        return $this;
    }

    public function removeConsommation(Consommation $consommation): static
    {
        if ($this->consommations->removeElement($consommation)) {
            if ($consommation->getLogement() === $this) {
                $consommation->setLogement(null);
            }
        }

        return $this;
    }
}
