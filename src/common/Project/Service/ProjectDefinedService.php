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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Service;

class ProjectDefinedService extends \Service
{
    private const DEFAULT_ICON = 'fas fa-angle-double-right';

    public static function forProjectServiceCreation(\Project $project): self
    {
        return new self(
            $project,
            [
                'service_id' => self::FAKE_ID_FOR_CREATION,
                'group_id' => $project->getID(),
                'label' => '',
                'description' => '',
                'short_name' => '',
                'link' => '#',
                'is_active' => 1,
                'is_used' => 1,
                'scope' => self::SCOPE_PROJECT,
                'rank' => 0,
                'location' => '',
                'server_id' => null,
                'is_in_iframe' => 0,
                'is_in_new_tab' => false,
                'icon' => self::DEFAULT_ICON,
            ],
        );
    }

    public function getIconName(): string
    {
        return ($this->data['icon'] !== '') ? $this->data['icon'] : self::DEFAULT_ICON;
    }

    public function isOpenedInNewTab(): bool
    {
        return (int) $this->data['is_in_new_tab'] === 1;
    }

    public function urlCanChange(): bool
    {
        return true;
    }
}
