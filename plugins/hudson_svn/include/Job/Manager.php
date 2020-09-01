<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\HudsonSvn\Job;

use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use Valid_UInt;
use Project;
use SVNPathsUpdater;

class Manager
{

    /**
     * @var SVNPathsUpdater
     */
    private $path_updater;

    /**
     * @var Tuleap\SVN\Repository\RepositoryManager
     */
    private $repository_manager;

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao, RepositoryManager $repository_manager, SVNPathsUpdater $path_updater)
    {
        $this->dao                = $dao;
        $this->repository_manager = $repository_manager;
        $this->path_updater       = $path_updater;
    }

    public function delete($job_id)
    {
        return $this->dao->deleteTrigger($job_id);
    }

    public function save(array $params)
    {
        $repository_id = $params['request']->get('hudson_use_plugin_svn_trigger');
        $valid_repo_id = new Valid_UInt('hudson_use_plugin_svn_trigger');
        $valid_repo_id->required();

        if (! $params['request']->valid($valid_repo_id) && ! $repository_id) {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-hudson_svn', 'This request is not valid.')
            );

            return;
        }

        if (! $this->doesRepositoryExist($params['request']->getProject(), $repository_id)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-hudson_svn', 'Repository not found.')
            );

            return;
        }

        $submitted_content = $params['request']->get('hudson_svn_trigger_path');
        $path              = $this->path_updater->transformContent($submitted_content);

        if (! $this->dao->saveTrigger($params['job_id'], $repository_id, $path)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-hudson_svn', 'An error occurred while adding job.')
            );
        }
    }

    private function doesRepositoryExist(Project $project, $repository_id)
    {
        try {
            $this->repository_manager->getByIdAndProject($repository_id, $project);
            return true;
        } catch (CannotFindRepositoryException $exception) {
            return false;
        }
    }
}
