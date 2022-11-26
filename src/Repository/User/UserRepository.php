<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Repository\User;

use Doctrine\Persistence\ManagerRegistry;
use DR\GitCommitNotification\Doctrine\EntityRepository\ServiceEntityRepository;
use DR\GitCommitNotification\Entity\User\User;

/**
 * @extends ServiceEntityRepository<User>
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findBySearchQuery(string $searchQuery, int $limit): array
    {
        $query = $this->createQueryBuilder('u')
            ->where('u.name LIKE :search or u.email LIKE :search')
            ->setParameter('search', addcslashes($searchQuery, '%_') . '%')
            ->orderBy('u.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery();

        /** @var User[] $result */
        $result = $query->getResult();

        return $result;
    }

    /**
     * @param int[] $userIds
     *
     * @return User[]
     */
    public function findUsersWithExclusion(array $userIds): array
    {
        $builder = $this->createQueryBuilder('u');
        if (count($userIds) > 0) {
            $builder->where($builder->expr()->notIn('u.id', $userIds));
        }

        /** @var User[] $result */
        $result = $builder->orderBy('u.name', 'ASC')->getQuery()->getResult();

        return $result;
    }
}
