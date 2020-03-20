<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Events;

use Backend;
use EventManager;
use Project;
use ProjectManager;
use System_Command;
use SystemEvent;
use SystemEventManager;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\Svn\ApacheConfGenerator;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\SvnAdmin;

class SystemEvent_SVN_RESTORE_REPOSITORY extends SystemEvent //phpcs:ignore
{
    public const NAME = 'SystemEvent_SVN_RESTORE_REPOSITORY';

    public function process()
    {
        $parameters = $this->getParametersAsArray();
        if (! empty($parameters[0])) {
            $project_id = (int) $parameters[0];
        } else {
            $this->error('Missing argument project id');
            return false;
        }
        if (! empty($parameters[1])) {
            $repository_id = (int) $parameters[1];
        } else {
            $this->error('Missing argument repository id');
            return false;
        }

        $project    = $this->getProject($project_id);
        $repository = $this->getRepository($project, $repository_id);

        if ((int) $repository->getProject()->getID() !== (int) $project_id) {
            $this->error('Bad project id');
            return false;
        }

        $repository_manager = $this->getRepositoryManager();
        $repository_manager->restoreRepository($repository);

        ApacheConfGenerator::build()->generate();

        $this->done();
        return true;
    }

    public function verbalizeParameters($with_link)
    {
        $project_id    = $this->getRequiredParameter(0);
        $repository_id = $this->getRequiredParameter(1);

        return 'project: ' . $this->verbalizeProjectId($project_id, $with_link) .
        ', repository: ' . $repository_id;
    }

    protected function getRepository(Project $project, $repository_id)
    {
        $repository_manager = $this->getRepositoryManager();
        return $repository_manager->getByIdAndProject($repository_id, $project);
    }

    protected function getProject($project_id)
    {
        $project_manager = ProjectManager::instance();

        return $project_manager->getProject($project_id);
    }

    private function getRepositoryManager()
    {
        return new RepositoryManager(
            new Dao(),
            ProjectManager::instance(),
            new SvnAdmin(new System_Command(), \SvnPlugin::getLogger(), Backend::instance(Backend::SVN)),
            \SvnPlugin::getLogger(),
            new System_Command(),
            new Destructor(
                new Dao(),
                \SvnPlugin::getLogger()
            ),
            EventManager::instance(),
            Backend::instance(Backend::SVN),
            new AccessFileHistoryFactory(new AccessFileHistoryDao()),
            SystemEventManager::instance()
        );
    }
}
