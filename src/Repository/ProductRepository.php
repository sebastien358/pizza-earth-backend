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

    public function findAllHomePublished()
    {
        return $this->createQueryBuilder('p')
            ->where('p.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();
    }

    public function findAllPublished(int $offset, int $limit)
    {
        return $this->createQueryBuilder('p')
            ->where('p.isVisible = :isVisible')
            ->setParameter('isVisible', true)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllSearch(string $search)
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->getQuery()
            ->getResult();
    }

    public function countProducts()
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
