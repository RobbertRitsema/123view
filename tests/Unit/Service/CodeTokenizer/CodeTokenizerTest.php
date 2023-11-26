<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\Service\CodeTokenizer;

use DR\Review\Service\CodeTokenizer\CodeCommentTokenizer;
use DR\Review\Service\CodeTokenizer\CodeStringTokenizer;
use DR\Review\Service\CodeTokenizer\CodeTokenizer;
use DR\Review\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeTokenizer::class)]
class CodeTokenizerTest extends AbstractTestCase
{
    private CodeTokenizer $tokenizer;

    public function setUp(): void
    {
        parent::setUp();
        $this->tokenizer = new CodeTokenizer(new CodeStringTokenizer(), new CodeCommentTokenizer());
    }

    public function testTokenize(): void
    {
        static::assertSame([[CodeTokenizer::TOKEN_CODE, 'foobar']], $this->tokenizer->tokenize('foobar'));
    }

    public function testTokenizeWithString(): void
    {
        static::assertSame(
            [[CodeTokenizer::TOKEN_CODE, 'foo'], [CodeTokenizer::TOKEN_STRING, '"bar"']],
            $this->tokenizer->tokenize('foo"bar"')
        );

        static::assertSame(
            [[CodeTokenizer::TOKEN_CODE, 'foo'], [CodeTokenizer::TOKEN_STRING, '"bar"'], [CodeTokenizer::TOKEN_CODE, 'foo']],
            $this->tokenizer->tokenize('foo"bar"foo')
        );
    }

    public function testTokenizeWithComment(): void
    {
        static::assertSame(
            [[CodeTokenizer::TOKEN_CODE, 'foo '], [CodeTokenizer::TOKEN_COMMENT, '// bar']],
            $this->tokenizer->tokenize('foo // bar')
        );
    }
}
