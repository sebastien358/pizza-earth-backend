<?php

namespace App\Repository;

use App\Entity\Testimonial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Testimonial>
 */
class TestimonialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Testimonial::class);
    }

    public function findAllTestimonials(int $current, int $limit)
    {
        return $this->createQueryBuilder('t')
            ->where('t.isPublished = :isPublished')
            ->setParameter('isPublished', true)
            ->orderBy('t.id', 'DESC')
            ->setFirstResult(($current - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublishedTestimonials(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.isPublished = :isPublished')
            ->setParameter('isPublished', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageRating(): float
    {
        $avg = $this->createQueryBuilder('t')
            ->select('AVG(t.rating)')
            ->where('t.isPublished = :isPublished')
            ->setParameter('isPublished', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $avg ? round((float) $avg, 1) : 0.0;
    }

    public function findAllTestimonialsAdmin(int $current, int $limit)
    {
        return $this->createQueryBuilder('t')
            ->setFirstResult(($current - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function testimonialSearchAdmin(string $searchTerm)
    {
        return $this->createQueryBuilder('t')
            ->where('LOWER(t.name) LIKE :search')
            ->setParameter('search', '%' . strtolower($searchTerm ). '%')
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countTestimonialsAdmin()
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageRatingAdmin(): float
    {
        $avg = $this->createQueryBuilder('t')
            ->select('AVG(t.rating)')
            ->getQuery()
            ->getSingleScalarResult();

        return $avg ? round((float) $avg, 1) : 0.0;
    }

//    /**
//     * @return Testimonial[] Returns an array of Testimonial objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Testimonial
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
