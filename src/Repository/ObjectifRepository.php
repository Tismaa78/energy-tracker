<?php

namespace App\Repository;

use App\Entity\Objectif;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objectif>
 */
class ObjectifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objectif::class);
    }

    /**
     * Objectifs actifs dont la période chevauche [debut, fin].
     *
     * @return Objectif[]
     */
    public function findActiveByUserAndTypeAndPeriod(User $user, int $typeEnergieId, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.typeEnergie = :typeId')
            ->andWhere('o.dateDebut <= :fin')
            ->andWhere('o.dateFin >= :debut')
            ->setParameter('user', $user)
            ->setParameter('typeId', $typeEnergieId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }
}
