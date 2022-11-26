<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\MessageHandler\Mail;

use DR\GitCommitNotification\Message\Comment\CommentUpdated;
use DR\GitCommitNotification\Message\MailNotificationInterface;
use DR\GitCommitNotification\Repository\Review\CommentRepository;
use DR\GitCommitNotification\Service\CodeReview\Comment\CommentMentionService;
use DR\GitCommitNotification\Service\Mail\CommentMailService;
use DR\GitCommitNotification\Utility\Arrays;
use DR\GitCommitNotification\Utility\Assert;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

class CommentUpdatedMailNotificationHandler implements MailNotificationHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly CommentMailService $mailService,
        private readonly CommentRepository $commentRepository,
        private readonly CommentMentionService $mentionService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(MailNotificationInterface $message): void
    {
        assert($message instanceof CommentUpdated);
        $this->logger?->info('MailNotificationMessageHandler: comment updated: ' . $message->commentId);

        $comment = $this->commentRepository->find($message->commentId);
        if ($comment === null) {
            // comment was removed before we could send it
            return;
        }

        $mentions = array_values($this->mentionService->getMentionedUsers((string)$comment->getMessage()));
        if (count($mentions) === 0) {
            return;
        }

        $originalMentions = $this->mentionService->getMentionedUsers($message->originalComment);
        $newMentions      = Arrays::unique(Arrays::diff($mentions, $originalMentions));

        if (count($newMentions) === 0) {
            return;
        }

        $this->logger?->info('MailNotificationMessageHandler: sending new mentions to comment');

        $this->mailService->sendNewCommentMail(Assert::notNull($comment->getReview()), $comment, $newMentions);
    }

    public static function accepts(): string
    {
        return CommentUpdated::class;
    }
}
