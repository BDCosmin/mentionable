<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\NoteVote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NoteVote>
 */
class NoteVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoteVote::class);
    }

//    /**
//     * @return NoteVote[] Returns an array of NoteVote objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NoteVote
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findOneByUserAndNote(User $user, Note $note): ?NoteVote
    {
        return $this->createQueryBuilder('nv')
            ->andWhere('nv.user = :user')
            ->andWhere('nv.note = :note')
            ->setParameter('user', $user)
            ->setParameter('note', $note)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
