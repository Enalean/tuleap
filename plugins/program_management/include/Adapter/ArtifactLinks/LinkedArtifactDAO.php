<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\ArtifactLinks;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\ArtifactLinks\SearchLinkedArtifacts;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;

class LinkedArtifactDAO extends DataAccessObject implements SearchLinkedArtifacts
{
    /**
     * @param int[] $submitted_links_to_check
     * @psalm-param non-empty-array<int> $submitted_links_to_check
     */
    #[\Override]
    public function doesArtifactHaveMirroredMilestonesInProvidedLinks(int $artifact_id, array $submitted_links_to_check): bool
    {
        $in_statement = EasyStatement::open()->in('tracker_changeset_value_artifactlink.artifact_id IN (?*)', $submitted_links_to_check);

        $sql = "SELECT NULL
                FROM tracker_artifact
                    INNER JOIN tracker_changeset_value ON (tracker_artifact.last_changeset_id = tracker_changeset_value.changeset_id)
                    INNER JOIN tracker_changeset_value_artifactlink ON (tracker_changeset_value.id = tracker_changeset_value_artifactlink.changeset_value_id)
                WHERE tracker_artifact.id = ?
                    AND tracker_changeset_value_artifactlink.nature = ?
                    AND $in_statement";

        $rows = $this->getDB()->run($sql, $artifact_id, TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, ...$in_statement->values());

        return count($rows) > 0;
    }
}
