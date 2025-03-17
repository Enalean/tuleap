<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Tuleap\DB\DataAccessObject;

final class ArtifactLinkFieldSpecificPropertiesDAO extends DataAccessObject implements SearchSpecificProperties
{
    /**
     * @return null | array{field_id: int, can_edit_reverse_links: 0|1}
     */
    public function searchByFieldId(int $field_id): ?array
    {
        $sql = 'SELECT field_id, can_edit_reverse_links FROM tracker_field_artifact_link WHERE field_id = ?';

        return $this->getDB()->row($sql, $field_id);
    }
}
