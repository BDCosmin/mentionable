<?php

namespace App\Repository;

use App\Entity\CommentReply;
use App\Entity\CommentReplyVote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentReplyVote>
 */
class CommentReplyVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentReplyVote::class);
    }

    public function findOneByUserAndReply(User $user, CommentReply $reply): ?CommentReplyVote
    {
        return $this->createQueryBuilder('rv')
            ->andWhere('rv.user = :user')
            ->andWhere('rv.reply = :reply')
            ->setParameter('user', $user)
            ->setParameter('reply', $reply)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
