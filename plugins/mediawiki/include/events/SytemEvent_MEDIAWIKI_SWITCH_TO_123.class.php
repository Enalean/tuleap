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

require_once dirname(__FILE__)."/../Migration/MediawikiMigrator.php";
require_once dirname(__FILE__)."/../MediawikiVersionManager.php";

class SystemEvent_MEDIAWIKI_SWITCH_TO_123 extends SystemEvent {
    const NAME = 'MEDIAWIKI_SWITCH_TO_123';

    /** @var Mediawiki_Migration_MediawikiMigrator **/
    private $mediawiki_migrator;

    /** @var ProjectManager **/
    private $project_manager;

    /** @var MediawikiVersionManager */
    private $version_manager;

    public function injectDependencies(
        Mediawiki_Migration_MediawikiMigrator $mediawiki_migrator,
        ProjectManager                        $project_manager,
        MediawikiVersionManager               $version_manager
    ) {
        $this->project_manager    = $project_manager;
        $this->mediawiki_migrator = $mediawiki_migrator;
        $this->version_manager    = $version_manager;
    }

    public function process() {
        $project = $this->getProjectFromParameters();

        try {
            $this->mediawiki_migrator->migrateProjectTo123($project);
            $this->version_manager->saveVersionForProject($project, MediawikiVersionManager::MEDIAWIKI_123_VERSION);
            $this->done();
        } catch (System_Command_CommandException $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function getProjectFromParameters() {
        $project_id = $this->getProjectIdFromParameters();
        return $this->project_manager->getProject($project_id);
    }

    private function getProjectIdFromParameters() {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }

    public function verbalizeParameters($with_link) {
       return 'Project: '. $this->getProjectIdFromParameters();
    }
}
