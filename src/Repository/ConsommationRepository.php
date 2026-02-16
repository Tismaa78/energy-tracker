<?php

namespace App\Repository;

use App\Entity\Consommation;
use App\Entity\Objectif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consommation>
 */
class ConsommationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consommation::class);
    }

    /**
     * @return Consommation[]
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.createdAt', 'DESC');

        if (!empty($filters['type_energie_id'])) {
            $qb->andWhere('c.typeEnergie = :typeId')
                ->setParameter('typeId', $filters['type_energie_id']);
        }
        if (!empty($filters['logement_id'])) {
            $qb->andWhere('c.logement = :logementId')
                ->setParameter('logementId', $filters['logement_id']);
        }
        if (!empty($filters['periode_debut'])) {
            $qb->andWhere('c.periodeFin >= :periodeDebut')
                ->setParameter('periodeDebut', $filters['periode_debut']);
        }
        if (!empty($filters['periode_fin'])) {
            $qb->andWhere('c.periodeDebut <= :periodeFin')
                ->setParameter('periodeFin', $filters['periode_fin']);
        }

        return $qb->getQuery()->getResult();
    }

    public function sommeValeurByUserAndPeriode(int $userId, \DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.valeur) as total')
            ->andWhere('c.user = :userId')
            ->andWhere('c.periodeDebut <= :fin')
            ->andWhere('c.periodeFin >= :debut')
            ->setParameter('userId', $userId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * @return array<int, array{type_energie: string, total: float}>
     */
    public function sommeValeurByUserAndPeriodeGroupByType(int $userId, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('t.id', 't.libelle', 'SUM(c.valeur) as total')
            ->join('c.typeEnergie', 't')
            ->andWhere('c.user = :userId')
            ->andWhere('c.periodeDebut <= :fin')
            ->andWhere('c.periodeFin >= :debut')
            ->setParameter('userId', $userId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('t.id')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $out[$row['id']] = [
                'type_energie' => $row['libelle'],
                'total' => (float) $row['total'],
            ];
        }

        return $out;
    }

    /**
     * Somme des consommations pour la période d'un objectif (même user, même type d'énergie).
     */
    public function sommeValeurForObjectif(Objectif $objectif): float
    {
        $typeEnergie = $objectif->getTypeEnergie();
        if (!$typeEnergie) {
            return 0.0;
        }

        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.valeur) as total')
            ->andWhere('c.user = :user')
            ->andWhere('c.typeEnergie = :typeEnergie')
            ->andWhere('c.periodeDebut <= :dateFin')
            ->andWhere('c.periodeFin >= :dateDebut')
            ->setParameter('user', $objectif->getUser())
            ->setParameter('typeEnergie', $typeEnergie)
            ->setParameter('dateDebut', $objectif->getDateDebut())
            ->setParameter('dateFin', $objectif->getDateFin())
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
