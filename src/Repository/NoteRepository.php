<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    //    /**
    //     * @return Note[] Returns an array of Note objects
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

    //    public function findOneBySomeField($value): ?Note
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByMention(string $nametag): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.content LIKE :mention')
            ->setParameter('mention', '%@' . $nametag . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithMentionedUser()
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.mentionedUser', 'mu')
            ->addSelect('mu')
            ->orderBy('n.publicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeedNotesForUser(array $ringIds, int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.ring', 'r')
            ->addSelect('r')
            ->where('n.ring IS NULL')
            ->orWhere('n.ring IN (:ringIds)')
            ->setParameter('ringIds', $ringIds)
            ->orderBy('n.publicationDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findNotesForRingWithPinnedFirst(int $ringId): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.mentionedUser', 'mu')
            ->addSelect('mu')
            ->where('n.ring = :ringId')
            ->andWhere('n.isFromRing = 1')
            ->setParameter('ringId', $ringId)
            ->orderBy('n.isPinned', 'DESC')
            ->addOrderBy('n.publicationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
