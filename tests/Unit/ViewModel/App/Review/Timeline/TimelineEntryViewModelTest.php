<?php
declare(strict_types=1);

namespace DR\Review\Tests\Unit\ViewModel\App\Review\Timeline;

use DR\Review\Entity\Review\CodeReviewActivity;
use DR\Review\Entity\Review\Comment;
use DR\Review\Entity\Revision\Revision;
use DR\Review\Tests\AbstractTestCase;
use DR\Review\ViewModel\App\Review\Timeline\TimelineEntryViewModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TimelineEntryViewModel::class)]
class TimelineEntryViewModelTest extends AbstractTestCase
{
    public function testAccessorPairs(): void
    {
        $activity = new CodeReviewActivity();
        $comment  = new Comment();
        $revision = new Revision();

        $model = new TimelineEntryViewModel([$activity], 'message', 'url');

        static::assertSame($comment, $model->setComment($comment)->getComment());
        static::assertSame($revision, $model->setRevision($revision)->getRevision());
    }
}
