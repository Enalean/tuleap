<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Git\REST;

use ForgeConfig;
use GitPlugin;
use Project;

class DatabaseInitialization extends \DatabaseInitialization {

    public function activateGitService(Project $project) {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));

        echo "Activate Git Service for project\n";

        $project_id = $this->mysqli->real_escape_string($project->getID());
        $short_name = $this->mysqli->real_escape_string(GitPlugin::SERVICE_SHORTNAME);

        $sql = "UPDATE service
                SET is_used = 1
                WHERE group_id = $project_id
                    AND short_name = '$short_name'";

        $this->mysqli->real_query($sql);
    }
}
