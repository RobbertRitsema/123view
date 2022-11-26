<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Tests\Unit\Service\Git\Checkout;

use DR\GitCommitNotification\Entity\Config\Repository;
use DR\GitCommitNotification\Entity\Review\Revision;
use DR\GitCommitNotification\Exception\RepositoryException;
use DR\GitCommitNotification\Git\GitRepository;
use DR\GitCommitNotification\Service\Git\CacheableGitRepositoryService;
use DR\GitCommitNotification\Service\Git\Checkout\GitCheckoutCommandBuilder;
use DR\GitCommitNotification\Service\Git\Checkout\GitCheckoutService;
use DR\GitCommitNotification\Service\Git\GitCommandBuilderFactory;
use DR\GitCommitNotification\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \DR\GitCommitNotification\Service\Git\Checkout\GitCheckoutService
 * @covers ::__construct
 */
class GitCheckoutServiceTest extends AbstractTestCase
{
    private CacheableGitRepositoryService&MockObject $repositoryService;
    private GitCommandBuilderFactory&MockObject      $builderFactory;
    private GitCheckoutService                       $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryService = $this->createMock(CacheableGitRepositoryService::class);
        $this->builderFactory    = $this->createMock(GitCommandBuilderFactory::class);
        $this->service           = new GitCheckoutService($this->repositoryService, $this->builderFactory);
    }

    /**
     * @covers ::checkout
     * @throws RepositoryException
     */
    public function testCheckout(): void
    {
        $repository = new Repository();
        $repository->setUrl('https://url/');
        $hash = '123abcdef';

        $builder = $this->createMock(GitCheckoutCommandBuilder::class);
        $builder->expects(self::once())->method('startPoint')->with($hash)->willReturnSelf();
        $this->builderFactory->expects(self::once())->method('createCheckout')->willReturn($builder);

        $git = $this->createMock(GitRepository::class);
        $git->expects(self::once())->method('execute')->with($builder)->willReturn('output');
        $this->repositoryService->expects(self::once())->method('getRepository')->with('https://url/')->willReturn($git);

        $this->service->checkout($repository, $hash);
    }

    /**
     * @covers ::checkoutRevision
     * @throws RepositoryException
     */
    public function testCheckoutRevision(): void
    {
        $hash       = '123abcdef';
        $repository = new Repository();
        $repository->setId(5);
        $repository->setUrl('https://url/');
        $revision = new Revision();
        $revision->setId(6);
        $revision->setRepository($repository);
        $revision->setCommitHash($hash);
        $branchName = 'repository-5-revision-6';

        $builder = $this->createMock(GitCheckoutCommandBuilder::class);
        $builder->expects(self::once())->method('branch')->with($branchName)->willReturnSelf();
        $builder->expects(self::once())->method('startPoint')->with($hash . '~')->willReturnSelf();
        $this->builderFactory->expects(self::once())->method('createCheckout')->willReturn($builder);

        $git = $this->createMock(GitRepository::class);
        $git->expects(self::once())->method('execute')->with($builder)->willReturn('output');
        $this->repositoryService->expects(self::once())->method('getRepository')->with('https://url/')->willReturn($git);

        $actualBranchName = $this->service->checkoutRevision($revision);
        static::assertSame($branchName, $actualBranchName);
    }
}
