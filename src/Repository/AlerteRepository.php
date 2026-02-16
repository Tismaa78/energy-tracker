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
}
