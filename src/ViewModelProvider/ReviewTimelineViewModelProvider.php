<?php
declare(strict_types=1);

namespace DR\Review\ViewModelProvider;

use DR\Review\Entity\Repository\Repository;
use DR\Review\Entity\Review\CodeReview;
use DR\Review\Entity\Revision\Revision;
use DR\Review\Entity\User\User;
use DR\Review\Message\Comment\CommentAdded;
use DR\Review\Message\Comment\CommentReplyAdded;
use DR\Review\Message\Revision\ReviewRevisionAdded;
use DR\Review\Repository\Review\CodeReviewActivityRepository;
use DR\Review\Repository\Review\CommentRepository;
use DR\Review\Service\CodeReview\Activity\CodeReviewActivityFormatter;
use DR\Review\Service\CodeReview\Activity\CodeReviewActivityUrlGenerator;
use DR\Review\ViewModel\App\Review\Timeline\TimelineEntryViewModel;
use DR\Review\ViewModel\App\Review\Timeline\TimelineViewModel;

class ReviewTimelineViewModelProvider
{
    public function __construct(
        private readonly CodeReviewActivityRepository $activityRepository,
        private readonly CodeReviewActivityFormatter $activityFormatter,
        private readonly CommentRepository $commentRepository,
        private readonly CodeReviewActivityUrlGenerator $urlGenerator,
        private readonly User $user
    ) {
    }

    /**
     * @param Revision[] $revisions
     */
    public function getTimelineViewModel(CodeReview $review, array $revisions): TimelineViewModel
    {
        $activities      = $this->activityRepository->findBy(['review' => $review->getId()], ['createTimestamp' => 'ASC']);
        $timelineEntries = [];

        // create TimelineEntryViewModel entries
        foreach ($activities as $activity) {
            $message = $this->activityFormatter->format($activity, $this->user);
            if ($message === null || $activity->getEventName() === CommentReplyAdded::NAME) {
                continue;
            }

            $timelineEntries[] = $entry = new TimelineEntryViewModel([$activity], $message, null);
            if ($activity->getEventName() === CommentAdded::NAME) {
                $entry->setComment($review->getComments()->get((int)$activity->getDataValue('commentId')));
            } elseif ($activity->getEventName() === ReviewRevisionAdded::NAME) {
                $entry->setRevision($revisions[(int)$activity->getDataValue('revisionId')] ?? null);
            }
        }

        return new TimelineViewModel($timelineEntries);
    }

    /**
     * @param string[] $events
     */
    public function getTimelineViewModelForFeed(User $user, array $events, ?Repository $repository = null): TimelineViewModel
    {
        $activities      = $this->activityRepository->findForUser($user->getId(), $events, $repository);
        $timelineEntries = [];

        // create TimelineEntryViewModel entries
        foreach ($activities as $activity) {
            $message = $this->activityFormatter->format($activity, $user);
            if ($message === null) {
                continue;
            }
            $comment = null;
            if ($activity->getEventName() === CommentAdded::NAME) {
                $comment = $this->commentRepository->find((int)$activity->getDataValue('commentId'));
                if ($comment === null) {
                    continue;
                }
            }
            $timelineEntries[] = (new TimelineEntryViewModel([$activity], $message, $this->urlGenerator->generate($activity)))->setComment($comment);
        }

        return new TimelineViewModel($timelineEntries);
    }
}
