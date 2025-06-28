<?php

namespace App\Repository;

use App\Entity\RingMembers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RingMembers>
 */
class RingMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RingMembers::class);
    }

//    /**
//     * @return RingMember[] Returns an array of RingMember objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?RingMember
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function countMembersByRings(): array
    {
        $qb = $this->createQueryBuilder('rm')
            ->select('IDENTITY(rm.ring) AS ringId, COUNT(rm.id) AS memberCount')
            ->groupBy('rm.ring');

        return $qb->getQuery()->getResult();
    }

}
