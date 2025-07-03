<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findLatestByReceiverWithSender(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.sender', 's')
            ->addSelect('s')
            ->leftJoin('n.ring', 'r')
            ->addSelect('r')
            ->andWhere('n.receiver = :user')
            ->andWhere('n.sender IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('n.notifiedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    public function findOneBySomeField($value): ?Notification
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
