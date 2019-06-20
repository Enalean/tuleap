<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Mediawiki;

require_once __DIR__ . '/../fusionforge/compat/forge_get_config.php';

use Project;

class MediawikiDataDir
{
    public function getMediawikiDir(Project $project)
    {
        $name_with_id        = forge_get_config('projects_path', 'mediawiki') . '/' . $project->getID();
        $name_with_shortname = forge_get_config('projects_path', 'mediawiki') . '/' . $project->getUnixName();

        if (is_dir($name_with_id)) {
            return $name_with_id;
        } elseif (is_dir($name_with_shortname)) {
            return $name_with_shortname;
        }
        return $name_with_id;
    }
}
