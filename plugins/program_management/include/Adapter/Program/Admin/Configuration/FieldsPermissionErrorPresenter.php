<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\FieldsPermissionError;

/**
 * @psalm-immutable
 */
final class FieldsPermissionErrorPresenter
{
    public string $field_url;
    public string $tracker_name;
    public string $project_name;
    public int $field_id;
    public string $label;

    public function __construct(FieldsPermissionError $permission_error)
    {
        $this->field_url    = '/plugins/tracker/permissions/fields-by-field/' .
            urlencode((string) $permission_error->tracker->getId()) . '?' .
            http_build_query(['selected_id' => $permission_error->field_id]);
        $this->tracker_name = $permission_error->tracker->getLabel();
        $this->project_name = $permission_error->project_reference->getProjectLabel();
        $this->field_id     = $permission_error->field_id;
        $this->label        = $permission_error->label;
    }
}
