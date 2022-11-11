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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\RequiredError;

/**
 * @psalm-immutable
 */
final class RequiredErrorPresenter
{
    public string $field_admin_url;
    public string $tracker_name;
    public string $team_project_label;
    public string $field_label;

    public function __construct(
        RequiredError $required_error,
    ) {
        $this->field_admin_url    = $required_error->field_admin_url;
        $this->tracker_name       = $required_error->tracker_name;
        $this->team_project_label = $required_error->team_project_label;
        $this->field_label        = $required_error->field_label;
    }
}
