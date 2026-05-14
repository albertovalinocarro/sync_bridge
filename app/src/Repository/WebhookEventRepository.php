<?php

namespace App\Repository;

use App\Entity\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookEvent>
 */
class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    public function findByFilters(
        ?string $clientId = null,
        ?string $status = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($clientId !== null) {
            $qb->andWhere('w.clientId = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($status !== null) {
            $qb->andWhere('w.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(
        ?string $clientId = null,
        ?string $status = null
    ): int {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)');
        
        if ($clientId !== null) {
            $qb->andWhere('w.clientId = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($status !== null) {
            $qb->andWhere('w.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
