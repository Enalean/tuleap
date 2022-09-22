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

/**
 * @psalm-immutable
 */
final class LinkedGroupPresenter
{
    public int $number_of_integrated_projects_in_last_sync = 15;
    public string $last_sync_time;

    public string $gitlab_url                 = 'https://gitlab.local/';
    public ?string $avatar_url                = null; // 'https://gitlab.local/uploads/-/system/group/avatar/9/organization_logo_small.png';
    public string $group_name                 = 'Touch Interaction Libraries';
    public string $first_letter_of_group_name = 't';
    public string $group_path                 = 'touch-interaction-libraries';
    public string $allow_artifact_closure     = 'No';
    public string $branch_prefix              = 'my_prefix';

    public function __construct(
        public GitLabLinkGroupPanePresenter $administration_pane,
        private \DateTimeImmutable $last_sync_date,
    ) {
        $this->last_sync_time = \DateHelper::timeAgoInWords($this->last_sync_date->getTimestamp(), true);
    }
}
