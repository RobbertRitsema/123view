<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\Git\Parser;

use DR\Review\Entity\Git\Diff\DiffBlock;
use DR\Review\Entity\Git\Diff\DiffFile;
use DR\Review\Exception\ParseException;
use DR\Review\Service\Parser\DiffFileParser;
use DR\Review\Service\Parser\DiffParser;
use DR\Review\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \DR\Review\Service\Parser\DiffParser
 * @covers ::__construct
 */
class DiffParserTest extends AbstractTestCase
{
    private DiffParser                $parser;
    private DiffFileParser&MockObject $fileParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileParser = $this->createMock(DiffFileParser::class);
        $this->parser     = new DiffParser($this->log, $this->fileParser);
    }

    /**
     * @covers ::parse
     */
    public function testParseDeletionsOnly(): void
    {
        $diffs = $this->parser->parse('test');
        static::assertCount(0, $diffs);
    }

    /**
     * @covers ::parse
     * @throws ParseException
     */
    public function testParseSingleFile(): void
    {
        $input = "\n";
        $input .= "diff --git a/example.txt b/example.txt\n";
        $input .= "foobar\n";

        $file = new DiffFile();

        // setup mocks
        $this->fileParser->expects(static::once())->method('parse')->with("foobar\n")->willReturn($file);

        $diffs = $this->parser->parse($input);
        static::assertSame([$file], $diffs);
    }

    /**
     * @covers ::parse
     * @throws ParseException
     */
    public function testParseSingleFileWithNew(): void
    {
        $input = "\n";
        $input .= "diff --git a/example with space/exampleA.txt b/example with space/exampleB.txt\n";
        $input .= "foobar\n";

        // setup mocks
        $this->fileParser->expects(static::once())->method('parse')->with("foobar\n")->willReturnArgument(1);

        $diffs = $this->parser->parse($input);
        static::assertCount(1, $diffs);
        $file = $diffs[0];
        static::assertSame('example with space/exampleA.txt', $file->filePathBefore);
        static::assertSame('example with space/exampleB.txt', $file->filePathAfter);
    }

    /**
     * @covers ::parse
     * @throws ParseException
     */
    public function testParseSingleFileWithMinimalSettings(): void
    {
        $input = "\n";
        $input .= "diff --git a/example with space/exampleA.txt b/example with space/exampleB.txt\n";
        $input .= "foobar\n";

        // setup mocks
        $this->fileParser->expects(static::once())
            ->method('parse')
            ->with("foobar\n")
            ->willReturnCallback(
                static function (string $output, DiffFile $file) {
                    $file->addBlock(new DiffBlock());

                    return $file;
                }
            );

        $diffs = $this->parser->parse($input, true);
        static::assertCount(1, $diffs);
        $file = $diffs[0];
        static::assertCount(0, $file->getBlocks());
    }

    /**
     * @covers ::parse
     * @throws ParseException
     */
    public function testParseTwoFiles(): void
    {
        $input = "\n";
        $input .= "diff --git a/example.txt b/example.txt\n";
        $input .= "foobar A\n";
        $input .= "diff --git a/example.git b/example.git\n";
        $input .= "foobar B\n";

        $fileA = new DiffFile();
        $fileB = new DiffFile();

        // setup mocks
        $this->fileParser->expects(static::exactly(2))->method('parse')
            ->withConsecutive(["foobar A"], ["foobar B\n"])
            ->willReturn($fileA, $fileB);

        $diffs = $this->parser->parse($input);
        static::assertSame([$fileA, $fileB], $diffs);
    }
}
