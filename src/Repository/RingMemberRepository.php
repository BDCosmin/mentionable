<?php

namespace App\Repository;

use App\Entity\RingMembers;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function findLastJoinedRingsByUser(UserInterface $user, int $limit = 4): array
    {
        return $this->createQueryBuilder('rm')
            ->andWhere('rm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rm.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveMembershipsForUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('rm')
            ->join('rm.ring', 'r')
            ->where('rm.user = :user')
            ->andWhere('r.isSuspended = 0')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

}
