<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function search(string $searchInput): array
    {
        // Validare minimă
        if (strlen($searchInput) < 2) {
            return [];
        }

        $qb = $this->createQueryBuilder('p');

        // Caută nametag-uri care conțin inputul
        $qb->andWhere('p.nametag LIKE :nametag')
            ->setParameter('nametag', '%' . $searchInput . '%');

        return $qb->getQuery()->getResult();
    }

    public function findByNametag(string $nametag): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nametag = :nametag')
            ->setParameter('nametag', $nametag)
            ->getQuery()
            ->useQueryCache(false)
            ->getResult();
    }

}
