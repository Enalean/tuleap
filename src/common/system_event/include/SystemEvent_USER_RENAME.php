<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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
 */

use Tuleap\SVNCore\Event\UpdateProjectAccessFilesEvent;

/**
 * Change User name
 *
 */
class SystemEvent_USER_RENAME extends SystemEvent
{
    public function setLog(string $log): void
    {
        if (! isset($this->log) || $this->log == '') {
            $this->log = $log;
        } else {
            $this->log .= PHP_EOL . $log;
        }
    }

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
        list($user_id, $new_name) = $this->getParametersAsArray();
        $txt                     .= 'user: ' . $this->verbalizeUserId($user_id, $with_link) . ' new name: ' . $new_name;
        return $txt;
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    public function process()
    {
        list($user_id, $new_name) = $this->getParametersAsArray();

        $renameState = true;
        if (($user = $this->getUser($user_id))) {
            $old_user_name = $user->getUserName();

            // Update DB
            if (! $this->updateDB($user, $new_name)) {
                $this->error('Could not update User (id:' . $user->getId() . ') from "' . $user->getUserName() . '" to "' . $new_name . '"');
                $renameState = $renameState & false;
            }

            // Rename SVN files
            $event_manager = EventManager::instance();
            foreach ($user->getAllProjects() as $project_id) {
                $project = $this->getProject($project_id);
                if ($project) {
                    $event_manager->dispatch(new UpdateProjectAccessFilesEvent($project));
                }
            }

            $params                  = [];
            $params['old_user_name'] = $old_user_name;
            $params['user']          = $user;

            EventManager::instance()->processEvent(self::class, $params);
        }

        if ($renameState) {
            $this->done();
        }

        return (bool) $renameState;
    }

     /**
     * Update database
     *
     * @param PFUser    $user     User to update
     * @param String  $new_name New name
     *
     * @return bool
     */
    protected function updateDB($user, $new_name)
    {
        $um = UserManager::instance();
        return $um->renameUser($user, $new_name);
    }
}
