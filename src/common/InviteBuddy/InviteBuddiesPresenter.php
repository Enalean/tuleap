<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

/**
 * @psalm-immutable
 */
class InviteBuddiesPresenter
{
    public readonly string $instance_name;
    public readonly bool $has_projects;

    /**
     * @param ProjectToBeInvitedIntoPresenter[] $projects
     */
    public function __construct(
        public readonly bool $can_buddies_be_invited,
        public readonly bool $is_limit_reached,
        public readonly int $max_limit_by_day,
        public readonly array $projects,
    ) {
        $this->instance_name = (string) \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME);
        $this->has_projects  = count($this->projects) > 0;
    }
}
