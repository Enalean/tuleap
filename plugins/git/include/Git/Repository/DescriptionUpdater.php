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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Repository;

use GitRepository;
use Feedback;
use GitDaoException;
use ProjectHistoryDao;
use Git_SystemEventManager;

class DescriptionUpdater
{
    const MAX_LENGTH = 1024;

    /**
     * @var Git_SystemEventManager
     */
    private $git_system_event_manager;

    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(ProjectHistoryDao $history_dao, Git_SystemEventManager $git_system_event_manager)
    {
        $this->history_dao              = $history_dao;
        $this->git_system_event_manager = $git_system_event_manager;
    }

    public function updateDescription(GitRepository $repository, $description)
    {
        if (strlen($description) > self::MAX_LENGTH) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_git', 'actions_long_description')
            );

            $this->redirect($repository);
            return false;
        }

        try {
            $repository->setDescription($description);
            $repository->save();

            $this->history_dao->groupAddHistory(
                "git_repo_update",
                $repository->getName() . ': update description',
                $repository->getProjectId()
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_git', 'actions_save_repo_process')
            );

            $this->redirect($repository);
            return true;
        } catch (GitDaoException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $exception->getMessage()
            );

            $this->redirect($repository);
            return false;
        }
    }

    private function redirect(GitRepository $repository)
    {
        $project_id    = $repository->getProjectId();
        $repository_id = $repository->getId();

        $query_parts = array(
            'action'   => 'repo_management',
            'group_id' => $project_id,
            'repo_id'  => $repository_id,
            'pane'     => 'settings'
        );

        $url = "/plugins/git/?" . http_build_query($query_parts);

        $GLOBALS['HTML']->redirect($url);
    }
}
