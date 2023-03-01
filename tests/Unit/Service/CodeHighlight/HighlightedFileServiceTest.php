<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\Service\CodeHighlight;

use DR\Review\Entity\Git\Diff\DiffBlock;
use DR\Review\Entity\Git\Diff\DiffChange;
use DR\Review\Entity\Git\Diff\DiffFile;
use DR\Review\Entity\Git\Diff\DiffLine;
use DR\Review\Service\CodeHighlight\FilenameToLanguageTranslator;
use DR\Review\Service\CodeHighlight\HighlightedFileService;
use DR\Review\Service\CodeHighlight\HighlightHtmlLineSplitter;
use DR\Review\Tests\AbstractTestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \DR\Review\Service\CodeHighlight\HighlightedFileService
 * @covers ::__construct
 */
class HighlightedFileServiceTest extends AbstractTestCase
{
    private FilenameToLanguageTranslator&MockObject $translator;
    private HttpClientInterface&MockObject          $httpClient;
    private HighlightHtmlLineSplitter&MockObject    $splitter;
    private HighlightedFileService                  $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(FilenameToLanguageTranslator::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->splitter   = $this->createMock(HighlightHtmlLineSplitter::class);
        $this->service    = new HighlightedFileService($this->translator, $this->httpClient, $this->splitter);
    }

    /**
     * @covers ::fromDiffFile
     * @throws Exception
     */
    public function testGetHighlightedFileUnknownLanguage(): void
    {
        $file                = new DiffFile();
        $file->filePathAfter = '';

        $this->translator->expects(self::once())->method('translate')->with('')->willReturn(null);

        $this->httpClient->expects(self::never())->method('request');
        $this->splitter->expects(self::never())->method('split');

        static::assertNull($this->service->fromDiffFile($file));
    }

    /**
     * @covers ::fromDiffFile
     * @throws Exception
     */
    public function testGetHighlightedFile(): void
    {
        $lineA               = new DiffLine(DiffLine::STATE_UNCHANGED, [new DiffChange(DiffChange::UNCHANGED, 'file-data')]);
        $block               = new DiffBlock();
        $block->lines        = [$lineA];
        $file                = new DiffFile();
        $file->filePathAfter = '/path/to/file.xml';
        $file->addBlock($block);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getContent')->willReturn('highlighted-data');

        $this->translator->expects(self::once())->method('translate')->with('/path/to/file.xml')->willReturn('xml');
        $this->httpClient->expects(self::once())->method('request')
            ->with('POST', '', ['query' => ['language' => 'xml'], 'body' => 'file-data'])
            ->willReturn($response);
        $this->splitter->expects(self::once())->method('split')->with('highlighted-data')->willReturn(['highlighted', 'data']);

        $actual = $this->service->fromDiffFile($file);
        static::assertNotNull($actual);
        static::assertSame($file->filePathAfter, $actual->filePath);
        static::assertSame(['highlighted', 'data'], ($actual->closure)());
    }

    /**
     * @covers ::fromDiffFile
     * @throws Exception
     */
    public function testGetHighlightedFileRequestFailure(): void
    {
        $block               = new DiffBlock();
        $block->lines        = [new DiffLine(DiffLine::STATE_UNCHANGED, [new DiffChange(DiffChange::UNCHANGED, 'file-data')])];
        $file                = new DiffFile();
        $file->filePathAfter = '/path/to/file.xml';
        $file->addBlock($block);

        $this->translator->expects(self::once())->method('translate')->with('/path/to/file.xml')->willReturn('xml');
        $this->httpClient->expects(self::once())->method('request')->willThrowException(new RuntimeException('error'));

        static::assertNull($this->service->fromDiffFile($file));
    }
}
