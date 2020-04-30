<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Tuleap\DB\DataAccessObject;

class ChangesetFromXmlDao extends DataAccessObject
{
    public function saveChangesetIsCreatedFromXml(
        int $import_timestamp,
        int $user_id,
        int $changeset_id
    ): void {
        $this->getDB()->run(
            'INSERT INTO plugin_tracker_changeset_from_xml (changeset_id, user_id, timestamp) VALUES (?, ?, ?)',
            $changeset_id,
            $user_id,
            $import_timestamp
        );
    }

    public function searchChangeset(int $changeset_id): ?array
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_tracker_changeset_from_xml WHERE changeset_id = ?',
            $changeset_id
        );
    }
}
