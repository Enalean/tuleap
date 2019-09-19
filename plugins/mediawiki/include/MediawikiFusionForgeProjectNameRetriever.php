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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class MediawikiFusionForgeProjectNameRetriever
{

    public function getFusionForgeProjectName($fusionforgeprojectname)
    {
        if ($fusionforgeprojectname !== null) {
            return $fusionforgeprojectname;
        }
        if (! $fusionforgeprojectname) {
            $administration_project = new Group(1);
            $fusionforgeprojectname     = $administration_project->getUnixName();
        }

        $wiki_url = substr($_SERVER['REQUEST_URI'], strlen('/plugins/mediawiki/wiki') + 1);
        $wiki_url_array = explode('/', $wiki_url);
        $fusionforgeprojectname = $wiki_url_array[0];
        return $fusionforgeprojectname;
    }
}
