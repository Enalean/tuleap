<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'Project.class.php';

/** This class import a project from a xml content */
class ProjectXMLImporter {

    /** @var EventManager */
    private $event_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var $project_manager */
    private $project_manager;

    public function __construct(EventManager $event_manager, UserManager $user_manager, ProjectManager $project_manager) {
        $this->event_manager   = $event_manager;
        $this->user_manager    = $user_manager;
        $this->project_manager = $project_manager;
    }

    /**
     * Import a project xml in a project on the behalf of a user
     *
     * @throws Exception
     *
     * @return SimpleXMLElement
     */
    public function import($project_id, $user_name, $xml_file_path) {
        $project     = $this->getProject($project_id);
        $user        = $this->getUser($user_name);
        $xml_content = new SimpleXMLElement(file_get_contents($xml_file_path, "r"));
        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'project'     => $project,
                'xml_content' => $xml_content
            )
        );
    }

    /**
     * @throws RuntimeException
     * @return Project
     */
    private function getProject($project_id) {
        $project = $this->project_manager->getProject($project_id);
        if (! $project || ($project && ($project->isError() || $project->isDeleted()))) {
            throw new RuntimeException('Invalid project_id '.$project_id);
        }
        return $project;
    }

    /**
     * @throws RuntimeException
     * @return PFUser
     */
    private function getUser($user_name) {
        $user = $this->user_manager->forceLogin($user_name);
        if (! $user->isSuperUser() || ! $user->isActive()) {
            throw new RuntimeException('Invalid username '.$user_name.'. User must be site admin and active');
        }
        return $user;
    }
}
?>
