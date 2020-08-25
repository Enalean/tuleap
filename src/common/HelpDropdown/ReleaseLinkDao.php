<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HelpDropdown;

use Tuleap\DB\DataAccessObject;

class ReleaseLinkDao extends DataAccessObject
{
    /**
     * @psalm-return array{actual_link:string, tuleap_version: string}
     */
    public function getReleaseLink(): ?array
    {
        $sql = "SELECT * FROM release_note_link LIMIT 1";

        return $this->getDB()->row($sql);
    }

    public function updateReleaseNoteLink(?string $actual_link, string $tuleap_version): void
    {
        $sql = "UPDATE release_note_link SET actual_link = ?, tuleap_version = ?";

        $this->getDB()->run($sql, $actual_link, $tuleap_version);
    }

    public function createReleaseNoteLink(string $tuleap_version): void
    {
        $this->getDB()->insert(
            "release_note_link",
            [
                "tuleap_version" => $tuleap_version
            ]
        );
    }
}
