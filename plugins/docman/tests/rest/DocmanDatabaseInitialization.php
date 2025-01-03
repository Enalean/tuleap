<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest;

require_once __DIR__ . '/../../../../tests/lib/DatabaseInitialisation.php';

use ForgeConfig;

final class DocmanDatabaseInitialization extends \DatabaseInitialization
{
    public function setup(\Project $project): void
    {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
        $this->enableWikiService($project);
        $this->defineAFileNamePattern($project);
    }

    private function enableWikiService(\Project $project): void
    {
        echo 'Enable the Wiki service to test the Docman' . PHP_EOL;

        $sql = "INSERT INTO tuleap.service (group_id, label, description, short_name, link, is_active, is_used, scope, `rank`, location, server_id, is_in_iframe)
                VALUES (?, 'Wiki', 'Wiki', 'wiki', ?, 1, 1, 'system', 105, 'master', NULL, 0)";

        $wiki_url   = '/wiki/?group_id=' . $project->getID();
        $statment   = $this->mysqli->prepare($sql);
        $project_id = $project->getID();
        $statment->bind_param('is', $project_id, $wiki_url);
        $statment->execute();
        $statment->close();
    }

    private function defineAFileNamePattern(\Project $project): void
    {
        if ($project->getUnixName() !== 'docmanproject') {
            return;
        }

        echo 'Add a custom file name pattern for created documents' . PHP_EOL;

        $sql = 'UPDATE plugin_docman_project_settings SET filename_pattern = ?, is_filename_pattern_enforced = ? WHERE group_id = ?';

        $statment    = $this->mysqli->prepare($sql);
        $project_id  = $project->getID();
        $pattern     = 'tuleap-${TITLE}';
        $is_enforced = true;
        $statment->bind_param('sii', $pattern, $is_enforced, $project_id);
        $statment->execute();
        $statment->close();
    }
}
