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

class SystemEvent_GIT_PROJECTS_UPDATE extends SystemEvent
{
    public const NAME = 'GIT_PROJECTS_UPDATE';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_GitoliteDriver */
    private $gitolite_driver;

    public function injectDependencies(
        \Psr\Log\LoggerInterface $logger,
        Git_SystemEventManager $system_event_manager,
        ProjectManager $project_manager,
        Git_GitoliteDriver $gitolite_driver
    ) {
        $this->logger               = new WrapperLogger($logger, self::NAME);
        $this->system_event_manager = $system_event_manager;
        $this->project_manager      = $project_manager;
        $this->gitolite_driver      = $gitolite_driver;
    }

    private function getProjectIdsFromParameters()
    {
        return array_map('intval', $this->getParametersAsArray());
    }

    public function process()
    {
        foreach ($this->getProjectIdsFromParameters() as $project_id) {
            $project = $this->project_manager->getProject($project_id);
            if ($project && ! $project->isError()) {
                $this->logger->debug('Update configuration for project ' . $project->getID());
                $this->gitolite_driver->dumpProjectRepoConf($project);
            }
        }
        $this->system_event_manager->queueGrokMirrorGitoliteAdminUpdate();

        $this->done();
    }

    public function verbalizeParameters($with_link)
    {
        return implode(', ', $this->getParametersAsArray());
    }
}
