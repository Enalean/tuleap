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

final class ReviewerChange implements TimelineEvent
{
    /**
     * @var DateTimeImmutable
     * @psalm-readonly
     */
    private $date_of_the_change;
    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user_doing_the_change;
    /**
     * @var array|PFUser[]
     * @psalm-readonly
     */
    private $added_reviewers;
    /**
     * @var array|PFUser[]
     * @psalm-readonly
     */
    private $removed_reviewers;

    /**
     * @param PFUser[] $added_reviewers
     * @param PFUser[] $removed_reviewers
     */
    public function __construct(
        DateTimeImmutable $date_of_the_change,
        PFUser $user_doing_the_change,
        array $added_reviewers,
        array $removed_reviewers
    ) {
        $this->date_of_the_change    = $date_of_the_change;
        $this->user_doing_the_change = $user_doing_the_change;
        $this->added_reviewers       = $added_reviewers;
        $this->removed_reviewers     = $removed_reviewers;
    }

    /**
     * @psalm-mutation-free
     */
    public function changedAt(): DateTimeImmutable
    {
        return $this->date_of_the_change;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPostDate(): int
    {
        return $this->date_of_the_change->getTimestamp();
    }

    /**
     * @psalm-mutation-free
     */
    public function changedBy(): PFUser
    {
        return $this->user_doing_the_change;
    }

    /**
     * @return PFUser[]
     * @psalm-mutation-free
     */
    public function getAddedReviewers(): array
    {
        return $this->added_reviewers;
    }

    /**
     * @return PFUser[]
     * @psalm-mutation-free
     */
    public function getRemovedReviewers(): array
    {
        return $this->removed_reviewers;
    }
}
