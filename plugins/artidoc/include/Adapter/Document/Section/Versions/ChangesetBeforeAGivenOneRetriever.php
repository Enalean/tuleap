<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document\Section\Versions;

use Tracker_Artifact_Changeset;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;

final class ChangesetBeforeAGivenOneRetriever extends DataAccessObject implements SearchChangesetsBeforeAGivenOne
{
    #[\Override]
    public function searchChangesetBefore(Artifact $artifact, int $changeset_id): Option
    {
        $sql = 'SELECT changeset.*
                FROM tracker_changeset AS changeset
                WHERE changeset.artifact_id = ?
                    AND changeset.id <= ?
                ORDER BY changeset.id DESC LIMIT 1';

        $row = $this->getDB()->row($sql, $artifact->getId(), $changeset_id);

        return $row
            ? Option::fromValue(
                new Tracker_Artifact_Changeset(
                    $row['id'],
                    $artifact,
                    $row['submitted_by'],
                    $row['submitted_on'],
                    $row['email']
                )
            )
            : Option::nothing(Tracker_Artifact_Changeset::class);
    }
}
