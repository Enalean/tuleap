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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\ProjectReference;

/**
 * @psalm-immutable
 */
final readonly class RequiredError
{
    public string $field_admin_url;
    public string $tracker_name;
    public string $team_project_label;

    public function __construct(
        int $field_id,
        public string $field_label,
        TrackerReference $tracker,
        ProjectReference $project_reference,
    ) {
        $this->field_admin_url    = $tracker->getURLToEditAField($field_id);
        $this->tracker_name       = $tracker->getLabel();
        $this->team_project_label = $project_reference->getProjectLabel();
    }
}
