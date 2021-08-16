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

/**
 * @psalm-immutable
 */
final class RequiredErrorPresenter
{
    public int $tracker_id;
    public int $field_id;
    public string $field_label;
    public string $field_admin_url;

    public function __construct(int $tracker_id, int $field_id, string $field_label)
    {
        $this->field_admin_url = '/plugins/tracker/?' .
            http_build_query(
                ['tracker' => $tracker_id, 'func' => 'admin-formElement-update', 'formElement' => $field_id]
            );
        $this->tracker_id      = $tracker_id;
        $this->field_id        = $field_id;
        $this->field_label     = $field_label;
    }
}
