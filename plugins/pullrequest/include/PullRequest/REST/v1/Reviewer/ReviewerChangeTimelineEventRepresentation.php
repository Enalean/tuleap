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
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;

final class ReviewerChangeTimelineEventRepresentation
{
    public readonly MinimalUserRepresentation $user;
    /**
     * @var string $post_date {@type date}
     */
    public readonly string $post_date;
    /**
     * @psalm-readonly
     */
    public string $type = 'reviewer-change';
    /**
     * @psalm-var list<MinimalUserRepresentation>
     */
    public readonly array $added_reviewers;
    /**
     * @psalm-var list<MinimalUserRepresentation>
     */
    public readonly array $removed_reviewers;

    /**
     * @param PFUser[] $added_reviewers
     * @param PFUser[] $removed_reviewers
     */
    private function __construct(PFUser $user, \DateTimeImmutable $post_date, array $added_reviewers, array $removed_reviewers, ProvideUserAvatarUrl $provide_user_avatar_url)
    {
        $this->user              = self::buildMinimalUserRepresentation($user, $provide_user_avatar_url);
        $this->post_date         = JsonCast::fromNotNullDateTimeToDate($post_date);
        $this->added_reviewers   = self::transformToMinimalUserRepresentations($provide_user_avatar_url, ...$added_reviewers);
        $this->removed_reviewers = self::transformToMinimalUserRepresentations($provide_user_avatar_url, ...$removed_reviewers);
    }

    private static function buildMinimalUserRepresentation(PFUser $user, ProvideUserAvatarUrl $provide_user_avatar_url): MinimalUserRepresentation
    {
        return MinimalUserRepresentation::build($user, $provide_user_avatar_url);
    }

    /**
     * @return list<MinimalUserRepresentation>
     */
    private static function transformToMinimalUserRepresentations(ProvideUserAvatarUrl $provide_user_avatar_url, PFUser ...$users): array
    {
        $minimal_user_representations = [];
        foreach ($users as $user) {
            $minimal_user_representations[] = self::buildMinimalUserRepresentation($user, $provide_user_avatar_url);
        }

        return $minimal_user_representations;
    }

    public static function fromReviewerChange(ReviewerChange $reviewer_change, ProvideUserAvatarUrl $provide_user_avatar_url): self
    {
        return new self(
            $reviewer_change->changedBy(),
            $reviewer_change->getPostDate(),
            $reviewer_change->getAddedReviewers(),
            $reviewer_change->getRemovedReviewers(),
            $provide_user_avatar_url,
        );
    }
}
