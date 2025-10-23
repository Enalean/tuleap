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

use Tuleap\Artidoc\Domain\Document\Section\Versions\CheckVersionExistsForArtidoc;
use Tuleap\DB\DataAccessObject;

final class VersionedSectionsDAO extends DataAccessObject implements CheckVersionExistsForArtidoc
{
    #[\Override]
    public function doesVersionBelongToASectionOfArtidoc(int $artidoc_id, int $version_id): bool
    {
        $sql = '
            SELECT 1
            FROM plugin_artidoc_section AS section
                INNER JOIN plugin_artidoc_section_version AS version ON (section.id = version.section_id)
                INNER JOIN tracker_changeset AS changeset ON (version.artifact_id = changeset.artifact_id)
            WHERE section.item_id = ? AND changeset.id = ?
        ';

        return $this->getDB()->exists($sql, $artidoc_id, $version_id);
    }
}
