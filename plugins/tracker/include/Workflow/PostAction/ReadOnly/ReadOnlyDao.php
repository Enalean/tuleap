<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\ReadOnly;

use Tuleap\DB\DataAccessObject;

class ReadOnlyDao extends DataAccessObject
{
    public function isFieldUsedInPostAction($field_id): bool
    {
        $sql = 'SELECT NULL
            FROM plugin_tracker_workflow_transition_postactions_read_only_fields AS paf
            WHERE paf.field_id = ?';
        $result = $this->getDB()->cell($sql, $field_id);

        return $result !== false;
    }

    public function searchByTransitionId(int $transition_id): array
    {
        $sql = 'SELECT paf.*
            FROM plugin_tracker_workflow_transition_postactions_read_only_fields AS paf
                 JOIN plugin_tracker_workflow_transition_postactions_read_only AS paro
            ON (paro.id = paf.postaction_id)
            WHERE paro.transition_id = ?';

        return $this->getDB()->q($sql, $transition_id);
    }
}
