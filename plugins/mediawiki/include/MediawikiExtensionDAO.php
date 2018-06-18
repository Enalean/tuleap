<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use Tuleap\DB\DataAccessObject;

class MediawikiExtensionDAO extends DataAccessObject
{
    public function saveMLEBActivationForProjectID($project_id)
    {
        $sql = 'INSERT INTO plugin_mediawiki_extension (project_id, extension_mleb) VALUES (?, 1)
                ON DUPLICATE KEY UPDATE extension_mleb = 1';

        $this->getDB()->run($sql, $project_id);
    }

    public function saveMathActivationForProjectID($project_id)
    {
        $sql = 'INSERT INTO plugin_mediawiki_extension (project_id, extension_math) VALUES (?, 1)
                ON DUPLICATE KEY UPDATE extension_math = 1';

        $this->getDB()->run($sql, $project_id);
    }

    public function getProjectIdsEligibleToMLEBExtensionActivation()
    {
        $sql = "SELECT pmv.project_id
                FROM plugin_mediawiki_version pmv
                LEFT JOIN plugin_mediawiki_extension pme
                  ON pme.project_id = pmv.project_id
                WHERE (pme.project_id IS NULL OR  pme.extension_mleb = 0)
                  AND pmv.mw_version = '1.23'";

        return $this->getDB()->column($sql);
    }

    public function isMLEBActivatedForProjectID($project_id)
    {
        return (bool) $this->getDB()->single(
            'SELECT extension_mleb FROM plugin_mediawiki_extension WHERE project_id = ?',
            [$project_id]
        );
    }

    public function isMathActivatedForProjectID($project_id)
    {
        return (bool) $this->getDB()->single(
            'SELECT extension_math FROM plugin_mediawiki_extension WHERE project_id = ?',
            [$project_id]
        );
    }
}
