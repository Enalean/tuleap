<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\Git\Repository\View\ParentRepositoryPresenter;
use Tuleap\Git\Repository\View\PresentPullRequest;

/**
 * @psalm-immutable
 */
final class PullRequestEmptyStatePresenter implements PresentPullRequest
{
    public function __construct(
        public readonly int $repository_id,
        public readonly int $project_id,
        public readonly bool $is_migrated_to_gerrit,
        public readonly ?ParentRepositoryPresenter $parent_repository_presenter,
    ) {
    }

    public function getTemplateName(): string
    {
        return 'pullrequest-empty-state';
    }
}
