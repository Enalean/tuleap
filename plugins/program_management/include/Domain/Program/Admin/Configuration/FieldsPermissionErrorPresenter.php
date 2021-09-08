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

use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\TrackerReference;

/**
 * @psalm-immutable
 */
final class FieldsPermissionErrorPresenter
{
    public string $field_url;
    public string $tracker_name;
    public string $project_name;

    public function __construct(public int $field_id, public string $label, TrackerReference $tracker, ProjectReference $project_reference)
    {
        $this->field_url    = '/plugins/tracker/permissions/fields-by-field/' .
            urlencode((string) $tracker->id) . '?' .
            http_build_query(['selected_id' => $this->field_id]);
        $this->tracker_name = $tracker->label;
        $this->project_name = $project_reference->getProjectLabel();
    }
}
