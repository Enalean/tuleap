<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once __DIR__ . '/../../../www/project/admin/project_admin_utils.php';

/**
 * Change project short name (unix_group_name)
 *
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_PROJECT_RENAME extends SystemEvent
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
        $txt                       = '';
        list($group_id, $new_name) = $this->getParametersAsArray();
        $txt                      .= 'project: ' . $this->verbalizeProjectId($group_id, $with_link) . ' new name: ' . $new_name;
        return $txt;
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    public function process()
    {
        list($group_id, $new_name) = $this->getParametersAsArray();

        $renameState = true;

        if (($project = $this->getProject($group_id))) {
            // Rename system home/groups
            $backendSystem = $this->getBackend('System');

            // Rename system FRS
            if (! $backendSystem->renameFileReleasedDirectory($project, $new_name)) {
                $this->error('Could not rename FRS repository (id:' . $project->getId() . ') from "' . $project->getUnixName() . '" to "' . $new_name . '"');
                $renameState = $renameState & false;
            }

            // Update DB
            if (! $this->updateDB($project, $new_name)) {
                $this->error('Could not update Project (id:' . $project->getId() . ') from "' . $project->getUnixName() . '" to "' . $new_name . '"');
                $renameState = $renameState & false;
            }

            // Add Hook for plugins
            $this->getEventManager()->processEvent(
                self::class,
                ['project'   => $project,
                    'new_name'  => $new_name,
                ]
            );
        } else {
            $renameState = false;
        }

        if ($renameState) {
            $this->addProjectHistory('rename_done', $project->getUnixName(false) . ' :: ' . $new_name, $project->getId());
            $this->done();
        } else {
            $this->addProjectHistory('rename_with_error', $project->getUnixName(false) . ' :: ' . $new_name . ' (event n°' . $this->getId() . ')', $project->getId());
        }

        return (bool) $renameState;
    }

    /**
     * Update database
     *
     * @param Project $project  Project to update
     * @param String  $new_name New name
     *
     * @return bool
     */
    protected function updateDB($project, $new_name)
    {
        $pm = ProjectManager::instance();
        return $pm->renameProject($project, $new_name);
    }

    /**
     * @param string  $field_name Event name
     * @param string  $old_value  Event value
     * @param int $group_id Project id of the vent
     *
     */
    protected function addProjectHistory($field_name, $old_value, $group_id)
    {
        (new ProjectHistoryDao())->groupAddHistory($field_name, $old_value, $group_id);
    }
}
