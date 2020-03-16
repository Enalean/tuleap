<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class SystemEvent_MEDIAWIKI_SWITCH_TO_123 extends SystemEvent
{
    public const NAME = 'MEDIAWIKI_SWITCH_TO_123';

    public const ALL = 'all';

    /** @var Mediawiki_Migration_MediawikiMigrator **/
    private $mediawiki_migrator;

    /** @var ProjectManager **/
    private $project_manager;

    /** @var MediawikiVersionManager */
    private $version_manager;

    /** @var MediawikiMLEBExtensionManager */
    private $mleb_manager;

    /** @var MediawikiSiteAdminResourceRestrictor */
    private $resource_restrictor;

    public function injectDependencies(
        Mediawiki_Migration_MediawikiMigrator $mediawiki_migrator,
        ProjectManager $project_manager,
        MediawikiVersionManager $version_manager,
        MediawikiMLEBExtensionManager $mleb_manager,
        MediawikiSiteAdminResourceRestrictor $resource_restrictor
    ) {
        $this->project_manager     = $project_manager;
        $this->mediawiki_migrator  = $mediawiki_migrator;
        $this->version_manager     = $version_manager;
        $this->mleb_manager        = $mleb_manager;
        $this->resource_restrictor = $resource_restrictor;
    }

    public function process()
    {
        try {
            $projects    = $this->getProjectsFromParameters();
            $nb_projects = count($projects);
            $count       = 0;
            foreach ($projects as $project) {
                if (file_exists(ForgeConfig::get('codendi_cache_dir') . '/STOP_SYSTEM_EVENT')) {
                    break;
                } else {
                    $this->migrateProject($project);
                    $count++;
                }
            }
            if ($count === $nb_projects) {
                $this->done("$nb_projects migrated");
            } else {
                $this->warning("Only $count/$nb_projects were migrated.");
            }
        } catch (System_Command_CommandException $exception) {
            $this->error($exception->getMessage());
        }
    }

    private function migrateProject(Project $project)
    {
        $this->resource_restrictor->allowProject($project);
        $this->mediawiki_migrator->migrateProjectTo123($project);
        $this->version_manager->saveVersionForProject($project, MediawikiVersionManager::MEDIAWIKI_123_VERSION);
        if ($this->mleb_manager->isMLEBExtensionInstalled()) {
            $this->mleb_manager->saveMLEBActivationForProject($project);
        }
    }

    private function getProjectsFromParameters()
    {
        if ($this->areAllProjectsMigrated()) {
            $projects = array();
            foreach ($this->version_manager->getAllProjectsToMigrateTo123() as $project_id) {
                $projects[] = $this->project_manager->getProject($project_id);
            }
            return $projects;
        }
        $project_id = $this->getProjectIdFromParameters();
        return array($this->project_manager->getProject($project_id));
    }

    private function areAllProjectsMigrated()
    {
        $parameters = $this->getParametersAsArray();
        return isset($parameters[0]) && $parameters[0] === self::ALL;
    }

    private function getProjectIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();
        return (int) $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        if ($this->areAllProjectsMigrated()) {
            return 'All projects';
        }
        return 'Project: ' . $this->getProjectIdFromParameters();
    }
}
