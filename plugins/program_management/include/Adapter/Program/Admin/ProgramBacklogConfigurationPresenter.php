<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin;

/**
 * @psalm-immutable
 */
final class ProgramBacklogConfigurationPresenter
{
    public bool $can_create_program;
    public bool $has_plan_permissions;
    public int $program_increment_tracker_id;
    public ?string $program_increment_label;
    public ?string $program_increment_sublabel;
    public bool $is_configured;
    public ?string $iteration_label;

    public function __construct(
        bool $can_create_program,
        bool $has_plan_permissions,
        int $program_increment_tracker_id,
        ?string $program_increment_label,
        ?string $program_increment_sublabel,
        bool $is_configured,
        ?string $iteration_label,
    ) {
        $this->can_create_program           = $can_create_program;
        $this->program_increment_tracker_id = $program_increment_tracker_id;
        $this->program_increment_label      = $program_increment_label;
        $this->program_increment_sublabel   = $program_increment_sublabel;
        $this->is_configured                = $is_configured;
        $this->has_plan_permissions         = $has_plan_permissions;
        $this->iteration_label              = $iteration_label;
    }
}
