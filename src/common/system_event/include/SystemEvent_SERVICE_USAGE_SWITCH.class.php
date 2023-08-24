<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */


/**
* System Event classes
*
*/
class SystemEvent_SERVICE_USAGE_SWITCH extends SystemEvent
{
    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        $txt                                  = '';
        list($group_id, $shortname, $is_used) = $this->getParametersAsArray();
        $txt                                 .= 'project: ' . $this->verbalizeProjectId($group_id, $with_link) . ', service: ' . $shortname . ', service is used: ' . ($is_used ? 'true' : 'false');
        return $txt;
    }

    /**
     * Process stored event
     */
    public function process()
    {
        list($group_id, $shortname, $is_used) = $this->getParametersAsArray();

        if ($project = $this->getProject($group_id)) {
            if (($shortname == 'svn') && ($is_used)) {
                $backendSVN = Backend::instanceSVN();
                if (! $backendSVN->createProjectSVN($group_id)) {
                    $this->error("Could not create SVN repo for project $group_id after service activation");
                    return false;
                }
                $backendSVN->setSVNApacheConfNeedUpdate();
            }

            $this->done();
            return true;
        }
        return false;
    }
}
