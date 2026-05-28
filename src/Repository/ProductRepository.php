<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[] Returns products matching the given filters
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->orderBy('p.name', 'ASC');

        if (!empty($filters['name'])) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', (int) $filters['category']);
        }

        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') {
            $qb->andWhere('p.price >= :priceMin')
               ->setParameter('priceMin', (int) $filters['priceMin']);
        }

        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') {
            $qb->andWhere('p.price <= :priceMax')
               ->setParameter('priceMax', (int) $filters['priceMax']);
        }

        if (isset($filters['stockMin']) && $filters['stockMin'] !== '') {
            $qb->andWhere('p.stock >= :stockMin')
               ->setParameter('stockMin', (int) $filters['stockMin']);
        }

        if (isset($filters['stockMax']) && $filters['stockMax'] !== '') {
            $qb->andWhere('p.stock <= :stockMax')
               ->setParameter('stockMax', (int) $filters['stockMax']);
        }

        return $qb->getQuery()->getResult();
    }
}
