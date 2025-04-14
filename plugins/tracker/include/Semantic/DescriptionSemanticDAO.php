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

namespace Tuleap\Tracker\Semantic;

use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

final class DescriptionSemanticDAO extends DataAccessObject implements RetrieveDescriptionField
{
    public function searchByTrackerId(int $tracker_id): Option
    {
        $sql      = 'SELECT field_id FROM tracker_semantic_description WHERE tracker_id = ?';
        $field_id = $this->getDB()->cell($sql, $tracker_id);
        return $field_id !== false
            ? Option::fromValue($field_id)
            : Option::nothing(\Psl\Type\int());
    }
}
