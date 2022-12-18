<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\Service\Git\Fetch;

use Carbon\CarbonInterface;
use DR\Review\Entity\Git\Fetch\BranchUpdate;
use DR\Review\Entity\Repository\Repository;
use DR\Review\Entity\Review\Revision;
use DR\Review\Repository\Review\RevisionRepository;
use DR\Review\Service\Git\Fetch\GitFetchRemoteRevisionService;
use DR\Review\Service\Git\Fetch\LockableGitFetchService;
use DR\Review\Service\Git\Log\LockableGitLogService;
use DR\Review\Tests\AbstractTestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \DR\Review\Service\Git\Fetch\GitFetchRemoteRevisionService
 * @covers ::__construct
 */
class GitFetchRemoteRevisionServiceTest extends AbstractTestCase
{
    private LockableGitLogService&MockObject   $logService;
    private LockableGitFetchService&MockObject $fetchService;
    private RevisionRepository&MockObject      $revisionRepository;
    private GitFetchRemoteRevisionService      $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->logService         = $this->createMock(LockableGitLogService::class);
        $this->fetchService       = $this->createMock(LockableGitFetchService::class);
        $this->revisionRepository = $this->createMock(RevisionRepository::class);
        $this->service            = new GitFetchRemoteRevisionService($this->logService, $this->fetchService, $this->revisionRepository);
        $this->service->setLogger($this->createMock(LoggerInterface::class));
    }

    /**
     * @covers ::fetchRevisionFromRemote
     * @throws Exception
     */
    public function testFetchRevisionFromRemote(): void
    {
        $repository = new Repository();
        $repository->setId(123);
        $change   = new BranchUpdate('from', 'to', 'oldBranch', 'newBranch');
        $commitA  = $this->createCommit();
        $commitB  = $this->createCommit();
        $revision = new Revision();
        $revision->setCreateTimestamp(time());

        $this->fetchService->expects(self::once())->method('fetch')->with($repository)->willReturn([$change]);
        $this->logService->expects(self::once())->method('getCommitsFromRange')->with($repository, 'from', 'to')->willReturn([$commitA]);
        $this->revisionRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['repository' => 123], ['createTimestamp' => 'DESC'])
            ->willReturn($revision);
        $this->logService->expects(self::once())
            ->method('getCommitsSince')
            ->with($repository, static::isInstanceOf(CarbonInterface::class), 5)
            ->willReturn([$commitB]);

        $result = $this->service->fetchRevisionFromRemote($repository, 5);

        static::assertSame([$commitB], $result);
    }
}
