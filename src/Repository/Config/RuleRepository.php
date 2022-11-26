<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Repository\Config;

use Doctrine\Persistence\ManagerRegistry;
use DR\GitCommitNotification\Doctrine\EntityRepository\ServiceEntityRepository;
use DR\GitCommitNotification\Entity\Notification\Frequency;
use DR\GitCommitNotification\Entity\Notification\Rule;

/**
 * @extends ServiceEntityRepository<Rule>
 * @method Rule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rule[]    findAll()
 * @method Rule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rule::class);
    }

    /**
     * @phpstan-param Frequency::* $frequency
     * @return Rule[] Returns an array of Rule objects
     */
    public function getActiveRulesForFrequency(bool $active, string $frequency): array
    {
        /** @var Rule[] $result */
        $result = $this->createQueryBuilder('r')
            ->leftJoin('r.ruleOptions', 'o')
            ->andWhere('r.active = :active')
            ->andWhere('o.frequency = :frequency')
            ->setParameter('active', $active ? 1 : 0)
            ->setParameter('frequency', $frequency)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
