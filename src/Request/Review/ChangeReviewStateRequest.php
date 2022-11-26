<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Request\Review;

use DigitalRevolution\SymfonyRequestValidation\AbstractValidatedRequest;
use DigitalRevolution\SymfonyRequestValidation\ValidationRules;
use DR\GitCommitNotification\Doctrine\Type\CodeReviewStateType;

class ChangeReviewStateRequest extends AbstractValidatedRequest
{
    public function getState(): string
    {
        return (string)$this->request->request->get('state');
    }

    protected function getValidationRules(): ?ValidationRules
    {
        return new ValidationRules(
            [
                'request' => [
                    'state' => 'required|string|in:' . implode(',', CodeReviewStateType::VALUES)
                ]
            ]
        );
    }
}
