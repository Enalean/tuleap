<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

use Tuleap\SVNCore\Event\UpdateProjectAccessFilesScheduler;

/**
* System Event classes
*
*/
class SystemEvent_MEMBERSHIP_CREATE extends SystemEvent
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
        $txt                      = '';
        list($group_id, $user_id) = $this->getParametersAsArray();
        $txt                     .= 'project: ' . $this->verbalizeProjectId($group_id, $with_link) . ', user to add: ' . $this->verbalizeUserId($user_id, $with_link);
        return $txt;
    }

    /**
     * Process stored event
     */
    public function process()
    {
        list($group_id,$user_id) = $this->getParametersAsArray();

        if ($project = $this->getProject($group_id)) {
            if ($user_id == 0) {
                return $this->setErrorBadParam();
            }

            // SVN access file
            (new UpdateProjectAccessFilesScheduler(SystemEventManager::instance()))->scheduleUpdateOfProjectAccessFiles($project);

            $this->done();
            return true;
        }
        return false;
    }
}
