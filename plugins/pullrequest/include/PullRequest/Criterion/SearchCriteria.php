<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Criterion;

use Tuleap\Option\Option;

/**
 * @psalm-readonly
 */
final class SearchCriteria
{
    /**
     * @var Option<StatusCriterion>
     */
    public readonly Option $status;

    /**
     * @psalm-param list<AuthorCriterion> $authors
     * @psalm-param list<LabelCriterion> $labels
     * @psalm-param list<KeywordCriterion> $search
     * @psalm-param list<TargetBranchCriterion> $target_branches
     * @psalm-param list<ReviewerCriterion> $reviewers
     * @psalm-param list<RelatedToCriterion> $related_to
     */
    public function __construct(
        ?StatusCriterion $status = null,
        public readonly array $authors = [],
        public readonly array $labels = [],
        public readonly array $search = [],
        public readonly array $target_branches = [],
        public readonly array $reviewers = [],
        public readonly array $related_to = [],
    ) {
        $this->status = Option::fromNullable($status);
    }
}
