<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Tests\Unit\Controller\App\Vcs;

use DR\GitCommitNotification\Controller\App\Vcs\FetchRevisionsController;
use DR\GitCommitNotification\Entity\Repository\Repository;
use DR\GitCommitNotification\Message\Revision\FetchRepositoryRevisionsMessage;
use DR\GitCommitNotification\Repository\Config\RepositoryRepository;
use DR\GitCommitNotification\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @coversDefaultClass \DR\GitCommitNotification\Controller\App\Vcs\FetchRevisionsController
 * @covers ::__construct
 */
class FetchRevisionsControllerTest extends AbstractTestCase
{
    private RepositoryRepository&MockObject $repositoryRepository;
    private MessageBusInterface&MockObject  $bus;
    private FetchRevisionsController        $controller;
    private Envelope                        $envelope;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryRepository = $this->createMock(RepositoryRepository::class);
        $this->bus                  = $this->createMock(MessageBusInterface::class);
        $this->envelope             = new Envelope(new stdClass(), []);
        $this->controller           = new FetchRevisionsController($this->repositoryRepository, $this->bus);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeFindById(): void
    {
        $repository = new Repository();
        $repository->setId(123);

        $this->repositoryRepository->expects(self::once())->method('find')->with('123')->willReturn($repository);
        $this->bus->expects(self::once())->method('dispatch')->with(new FetchRepositoryRevisionsMessage(123))->willReturn($this->envelope);

        $response = ($this->controller)('123');
        static::assertEquals(new Response('Accepted'), $response);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeFindByName(): void
    {
        $repository = new Repository();
        $repository->setId(123);

        $this->repositoryRepository->expects(self::once())->method('findOneBy')->with(['name' => 'name'])->willReturn($repository);
        $this->bus->expects(self::once())->method('dispatch')->with(new FetchRepositoryRevisionsMessage(123))->willReturn($this->envelope);

        $response = ($this->controller)('name');
        static::assertEquals(new Response('Accepted'), $response);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeUnknownRepository(): void
    {
        $this->repositoryRepository->expects(self::once())->method('find')->with('123')->willReturn(null);
        $this->bus->expects(self::never())->method('dispatch');

        $response = ($this->controller)('123');
        static::assertEquals(new Response('Rejected', Response::HTTP_BAD_REQUEST), $response);
    }
}
