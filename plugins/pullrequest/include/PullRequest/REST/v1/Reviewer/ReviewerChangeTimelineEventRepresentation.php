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

namespace Tuleap\PullRequest\REST\v1\Reviewer;

use PFUser;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChange;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

final class ReviewerChangeTimelineEventRepresentation
{
    /**
     * @var MinimalUserRepresentation
     * @psalm-readonly
     */
    public $user;
    /**
     * @var string {@type date}
     * @psalm-readonly
     */
    public $post_date;
    /**
     * @var string
     * @psalm-readonly
     */
    public $type = 'reviewer-change';
    /**
     * @var MinimalUserRepresentation[]
     * @psalm-var list<MinimalUserRepresentation>
     * @psalm-readonly
     */
    public $added_reviewers;
    /**
     * @var MinimalUserRepresentation[]
     * @psalm-var list<MinimalUserRepresentation>
     * @psalm-readonly
     */
    public $removed_reviewers;

    /**
     * @param PFUser[] $added_reviewers
     * @param PFUser[] $removed_reviewers
     */
    private function __construct(PFUser $user, int $post_date, array $added_reviewers, array $removed_reviewers)
    {
        $this->user              = self::buildMinimalUserRepresentation($user);
        $this->post_date         = JsonCast::toDate($post_date);
        $this->added_reviewers   = self::transformToMinimalUserRepresentations(...$added_reviewers);
        $this->removed_reviewers = self::transformToMinimalUserRepresentations(...$removed_reviewers);
    }

    private static function buildMinimalUserRepresentation(PFUser $user): MinimalUserRepresentation
    {
        $minimal_user_representation = new MinimalUserRepresentation();
        $minimal_user_representation->build($user);

        return $minimal_user_representation;
    }

    /**
     * @return MinimalUserRepresentation[]
     */
    private static function transformToMinimalUserRepresentations(PFUser ...$users): array
    {
        $minimal_user_representations = [];
        foreach ($users as $user) {
            $minimal_user_representations[] = self::buildMinimalUserRepresentation($user);
        }

        return $minimal_user_representations;
    }

    public static function fromReviewerChange(ReviewerChange $reviewer_change): self
    {
        return new self(
            $reviewer_change->changedBy(),
            $reviewer_change->getPostDate(),
            $reviewer_change->getAddedReviewers(),
            $reviewer_change->getRemovedReviewers(),
        );
    }
}
