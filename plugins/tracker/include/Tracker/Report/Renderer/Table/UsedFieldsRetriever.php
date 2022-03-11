<?php
/**
 * Copyright (c) Enalean, 2022 — Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer\Table;

use PFUser;
use Tracker_FormElement_Field;
use Tracker_Report_Renderer_Table;

class UsedFieldsRetriever
{
    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getUsedFieldsInRendererUserCanSee(
        PFUser $current_user,
        Tracker_Report_Renderer_Table $renderer_table,
    ): array {
        $used_fields = [];

        foreach ($renderer_table->getColumns() as $column) {
            if (! isset($column['field'])) {
                continue;
            }
            $field = $column['field'];
            assert($field instanceof Tracker_FormElement_Field);

            if (! $field->userCanRead($current_user)) {
                continue;
            }

            $used_fields[] = $field;
        }

        return $used_fields;
    }
}
