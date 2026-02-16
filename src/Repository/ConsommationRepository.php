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
        if (isset($filters['valeur_min']) && $filters['valeur_min'] !== '' && $filters['valeur_min'] !== null) {
            $qb->andWhere('c.valeur >= :valeurMin')
                ->setParameter('valeurMin', (float) $filters['valeur_min']);
        }
        if (isset($filters['valeur_max']) && $filters['valeur_max'] !== '' && $filters['valeur_max'] !== null) {
            $qb->andWhere('c.valeur <= :valeurMax')
                ->setParameter('valeurMax', (float) $filters['valeur_max']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{items: Consommation[], total: int}
     */
    public function findByUserPaginated(int $userId, array $filters = [], int $page = 1, int $limit = 15, string $sortBy = 'createdAt', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId);

        if (!empty($filters['type_energie_id'])) {
            $qb->andWhere('c.typeEnergie = :typeId')->setParameter('typeId', $filters['type_energie_id']);
        }
        if (!empty($filters['logement_id'])) {
            $qb->andWhere('c.logement = :logementId')->setParameter('logementId', $filters['logement_id']);
        }
        if (!empty($filters['periode_debut'])) {
            $qb->andWhere('c.periodeFin >= :periodeDebut')->setParameter('periodeDebut', $filters['periode_debut']);
        }
        if (!empty($filters['periode_fin'])) {
            $qb->andWhere('c.periodeDebut <= :periodeFin')->setParameter('periodeFin', $filters['periode_fin']);
        }
        if (isset($filters['valeur_min']) && $filters['valeur_min'] !== '' && $filters['valeur_min'] !== null) {
            $qb->andWhere('c.valeur >= :valeurMin')->setParameter('valeurMin', (float) $filters['valeur_min']);
        }
        if (isset($filters['valeur_max']) && $filters['valeur_max'] !== '' && $filters['valeur_max'] !== null) {
            $qb->andWhere('c.valeur <= :valeurMax')->setParameter('valeurMax', (float) $filters['valeur_max']);
        }

        $allowedSort = ['createdAt', 'valeur', 'periodeDebut', 'periodeFin'];
        $sortBy = \in_array($sortBy, $allowedSort, true) ? $sortBy : 'createdAt';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) (clone $qb)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->orderBy('c.' . $sortBy, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
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
     * @return array<int, array{type_energie: string, total: float, cout: float}>
     */
    public function sommeValeurAndCoutByUserAndPeriodeGroupByType(int $userId, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('t.id', 't.libelle', 'SUM(c.valeur) as total', 'SUM(c.coutEstime) as cout')
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
                'cout' => (float) ($row['cout'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Somme des coûts estimés sur la période.
     */
    public function sommeCoutByUserAndPeriode(int $userId, \DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.coutEstime) as total')
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
     * Top logement(s) le plus consommateur sur la période (somme valeur). Retourne [{logement, total}, ...].
     *
     * @return array<int, array{logement: \App\Entity\Logement, total: float}>
     */
    public function topLogementsByUserAndPeriode(\App\Entity\User $user, \DateTimeInterface $debut, \DateTimeInterface $fin, int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('l.id', 'SUM(c.valeur) as total')
            ->join('c.logement', 'l')
            ->andWhere('c.user = :user')
            ->andWhere('c.periodeDebut <= :fin')
            ->andWhere('c.periodeFin >= :debut')
            ->andWhere('c.logement IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('l.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $logementIds = array_column($rows, 'id');
        if (empty($logementIds)) {
            return [];
        }

        $logementRepo = $this->getEntityManager()->getRepository(\App\Entity\Logement::class);
        $logements = $logementRepo->createQueryBuilder('l')
            ->andWhere('l.id IN (:ids)')
            ->setParameter('ids', $logementIds)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($logements as $l) {
            $byId[$l->getId()] = $l;
        }

        $out = [];
        foreach ($rows as $row) {
            if (isset($byId[$row['id']])) {
                $out[] = ['logement' => $byId[$row['id']], 'total' => (float) $row['total']];
            }
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

    /**
     * Évolution de la conso sur les 30 derniers jours : somme des valeurs par date de début de période.
     * Retourne ['labels' => ['01/02', ...], 'values' => [10.5, ...]].
     *
     * @return array{labels: string[], values: float[]}
     */
    public function evolutionLast30DaysByUser(int $userId): array
    {
        $fin = new \DateTimeImmutable('today');
        $debut = $fin->modify('-29 days');
        $rows = $this->createQueryBuilder('c')
            ->select('c.periodeDebut as jour', 'SUM(c.valeur) as total')
            ->andWhere('c.user = :userId')
            ->andWhere('c.periodeDebut >= :debut')
            ->andWhere('c.periodeDebut <= :fin')
            ->setParameter('userId', $userId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.periodeDebut')
            ->getQuery()
            ->getResult();

        $byDay = [];
        foreach ($rows as $row) {
            if ($row['jour'] instanceof \DateTimeInterface) {
                $key = $row['jour']->format('Y-m-d');
            } else {
                $key = (string) $row['jour'];
            }
            $byDay[$key] = (float) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $fin->modify("-{$i} days");
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $values[] = $byDay[$key] ?? 0.0;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
