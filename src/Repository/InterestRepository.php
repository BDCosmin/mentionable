<?php

namespace App\Repository;

use App\Entity\Interest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interest>
 */
class InterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interest::class);
    }

//    /**
//     * @return Interest[] Returns an array of Interest objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Interest
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findUsersWithInterest(string $title): array
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT u FROM App\Entity\User u
     JOIN u.interests i
     WHERE i.title = :title'
        )->setParameter('title', $title);

        return $query->getResult();

    }
}
