<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class MediawikiLanguageDao extends DataAccessObject
{

    public function getUsedLanguageForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT language
                 FROM plugin_mediawiki_admin_options
                 WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function updateLanguageOption($project_id, $language)
    {
        $project_id = $this->da->escapeInt($project_id);
        $language   = $this->da->quoteSmart($language);

        $sql = "INSERT INTO plugin_mediawiki_admin_options (project_id, language)
                 VALUES ($project_id, $language)
                 ON DUPLICATE KEY
                     UPDATE language = VALUES(language)";

        return $this->update($sql);
    }
}
