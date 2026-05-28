<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findBySeller(User $seller): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.seller = :seller')
            ->setParameter('seller', $seller)
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.author = :author')
            ->setParameter('author', $author)
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAuthorAndSeller(User $author, User $seller): ?Review
    {
        return $this->findOneBy(['author' => $author, 'seller' => $seller]);
    }

    public function getAverageRating(User $seller): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avg')
            ->andWhere('r.seller = :seller')
            ->setParameter('seller', $seller)
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (float) $result : null;
    }
}
