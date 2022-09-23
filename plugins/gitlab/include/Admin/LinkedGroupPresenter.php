<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Admin;

use Tuleap\Gitlab\Group\GroupLink;

/**
 * @psalm-immutable
 */
final class LinkedGroupPresenter
{
    public string $last_sync_time;
    public string $gitlab_url;
    public ?string $avatar_url;
    public string $group_name;
    public string $first_letter_of_group_name;
    public string $group_path;
    public string $allow_artifact_closure;
    public string $branch_prefix;

    public function __construct(
        public GitLabLinkGroupPanePresenter $administration_pane,
        GroupLink $group_link,
        public int $number_of_integrated_projects_in_last_sync,
    ) {
        $this->group_name                 = $group_link->name;
        $this->first_letter_of_group_name = mb_substr($group_link->name, 0, 1);
        $this->group_path                 = $group_link->full_path;
        $this->allow_artifact_closure     = $group_link->allow_artifact_closure
            ? dgettext('tuleap-gitlab', 'Yes')
            : dgettext('tuleap-gitlab', 'No');
        $this->branch_prefix              = $group_link->prefix_branch_name ?? '';
        $this->gitlab_url                 = $group_link->web_url;
        $this->avatar_url                 = $group_link->avatar_url;

        $this->last_sync_time = \DateHelper::timeAgoInWords(
            $group_link->last_synchronization_date->getTimestamp(),
            true
        );
    }
}
