<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Permission;

use Tuleap\Tracker\Permission\SearchUserGroupsPermissionOnTrackers;

final class SearchUserGroupsPermissionOnTrackersStub implements SearchUserGroupsPermissionOnTrackers
{
    /**
     * @var list<int>
     */
    private array $view_results = [];
    /**
     * @var list<int>
     */
    private array $submit_results = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @param list<int> $results
     */
    public function withViewResults(array $results): self
    {
        $this->view_results = $results;

        return $this;
    }

    /**
     * @param list<int> $results
     */
    public function withSubmitResults(array $results): self
    {
        $this->submit_results = $results;

        return $this;
    }

    #[\Override]
    public function searchUserGroupsViewPermissionOnTrackers(array $user_groups, array $trackers_id): array
    {
        return $this->view_results;
    }

    #[\Override]
    public function searchUserGroupsSubmitPermissionOnTrackers(array $user_groups_id, array $trackers_id): array
    {
        return $this->submit_results;
    }
}
