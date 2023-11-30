<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Reviewer\Change;

use DateTimeImmutable;
use PFUser;
use Tuleap\PullRequest\Timeline\TimelineEvent;

/**
 * @psalm-mutation-free
 */
final class ReviewerChange implements TimelineEvent
{
    /**
     * @param PFUser[] $added_reviewers
     * @param PFUser[] $removed_reviewers
     */
    public function __construct(
        private readonly \DateTimeImmutable $date_of_the_change,
        private readonly PFUser $user_doing_the_change,
        private readonly array $added_reviewers,
        private readonly array $removed_reviewers,
    ) {
    }

    public function changedAt(): DateTimeImmutable
    {
        return $this->date_of_the_change;
    }

    public function getPostDate(): \DateTimeImmutable
    {
        return $this->date_of_the_change;
    }

    public function changedBy(): PFUser
    {
        return $this->user_doing_the_change;
    }

    /**
     * @return PFUser[]
     */
    public function getAddedReviewers(): array
    {
        return $this->added_reviewers;
    }

    /**
     * @return PFUser[]
     */
    public function getRemovedReviewers(): array
    {
        return $this->removed_reviewers;
    }
}
