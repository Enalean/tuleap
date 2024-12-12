<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class Mediawiki_Migration_MediawikiMigrator
{
    /**
     * @throws System_Command_CommandException
     */
    public function migrateProjectTo123(Project $project)
    {
        $this->runUpdateScript($project);
    }

    public function runUpdateScript(Project $project)
    {
        $system_execution = new System_Command();
        $system_execution->exec($this->getCommandToExecute($project));
    }

    private function getCommandToExecute(Project $project)
    {
        return __DIR__ . '/../../bin/migrate_to_123.php ' . escapeshellarg($project->getUnixName()) . ' --conf ' . __DIR__ . '/../../www/LocalSettings.php --quick';
    }
}
