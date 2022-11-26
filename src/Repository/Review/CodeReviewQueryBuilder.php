<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Repository\Review;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use DR\GitCommitNotification\Entity\Review\CodeReview;
use DR\GitCommitNotification\Entity\User\User;

class CodeReviewQueryBuilder
{
    private readonly QueryBuilder $queryBuilder;

    public function __construct(string $alias, EntityManagerInterface $em)
    {
        $this->queryBuilder = $em->createQueryBuilder()->select($alias)->from(CodeReview::class, $alias);
    }

    public function prepare(int $repositoryId): self
    {
        $this->queryBuilder
            ->select('r', 'rv')
            ->leftJoin('r.revisions', 'rv')
            ->where('r.repository = :repositoryId')
            ->setParameter('repositoryId', $repositoryId)
            ->orderBy('r.id', 'DESC');

        return $this;
    }

    public function paginate(int $page, int $pageSize): self
    {
        $this->queryBuilder
            ->setFirstResult(max(0, $page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return $this;
    }

    public function search(User $user, string $searchQuery): self
    {
        if (preg_match('/id:(\d+)/', $searchQuery, $matches) === 1) {
            $this->queryBuilder->andWhere('r.projectId = :id')->setParameter('id', $matches[1]);
            $searchQuery = trim(str_replace($matches[0], '', $searchQuery));
        }

        if (preg_match('/state:(\w+)/', $searchQuery, $matches) === 1) {
            $this->queryBuilder->andWhere('r.state = :state')->setParameter('state', $matches[1]);
            $searchQuery = trim(str_replace($matches[0], '', $searchQuery));
        }

        if (preg_match('/author:(\S+)/', $searchQuery, $matches) === 1) {
            // search for current user
            if ($matches[1] === 'me') {
                $this->queryBuilder->andWhere('rv.authorEmail = :authorEmail');
                $this->queryBuilder->setParameter('authorEmail', (string)$user->getEmail());
            } else {
                $this->queryBuilder->andWhere('rv.authorEmail LIKE :searchAuthor OR rv.authorName LIKE :searchAuthor');
                $this->queryBuilder->setParameter('searchAuthor', '%' . addcslashes($matches[1], '%_') . '%');
            }
            $searchQuery = trim(str_replace($matches[0], '', $searchQuery));
        }

        if ($searchQuery === '') {
            return $this;
        }

        if (preg_match('/^\d+$/', $searchQuery) === 1) {
            $this->queryBuilder->andWhere('r.title LIKE :title OR r.projectId = :projectId')
                ->setParameter('projectId', $searchQuery)
                ->setParameter('title', '%' . addcslashes($searchQuery, "%_") . '%');
        } else {
            $this->queryBuilder->andWhere('r.title LIKE :title')
                ->setParameter('title', '%' . addcslashes($searchQuery, "%_") . '%');
        }

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->queryBuilder->getQuery();
    }
}
