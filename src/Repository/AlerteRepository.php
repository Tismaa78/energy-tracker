<?php

namespace App\Repository;

use App\Entity\Alerte;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alerte>
 */
class AlerteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alerte::class);
    }

    /**
     * @return Alerte[]
     */
    public function findByUser(User $user, bool $unreadOnly = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.consommation', 'c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateAlerte', 'DESC');

        if ($unreadOnly) {
            $qb->andWhere('a.estLue = false');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{items: Alerte[], total: int}
     */
    public function findByUserPaginated(User $user, bool $unreadOnly, int $page = 1, int $limit = 15, string $sortBy = 'dateAlerte', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.consommation', 'c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        if ($unreadOnly) {
            $qb->andWhere('a.estLue = false');
        }

        $allowedSort = ['dateAlerte', 'estLue', 'typeAlerte'];
        $sortBy = \in_array($sortBy, $allowedSort, true) ? $sortBy : 'dateAlerte';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $total = (int) (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->orderBy('a.' . $sortBy, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }
}
