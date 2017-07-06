<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use ProjectHistoryDao;
use SystemEvent;
use SystemEventManager;
use Tuleap\Svn\Dao;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;

class RepositoryCreator
{
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(
        Dao $dao,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $history_dao
    ) {
        $this->dao                  = $dao;
        $this->system_event_manager = $system_event_manager;
        $this->history_dao          = $history_dao;
    }

    /**
     * @param Repository $svn_repository
     *
     * @return SystemEvent or null
     * @throws CannotCreateRepositoryException
     */
    public function create(Repository $svn_repository)
    {
        $id = $this->dao->create($svn_repository);
        if (! $id) {
            throw new CannotCreateRepositoryException($GLOBALS['Language']->getText('plugin_svn', 'update_error'));
        }

        $svn_repository->setId($id);

        $this->history_dao->groupAddHistory(
            'svn_multi_repository_creation',
            $svn_repository->getName(),
            $svn_repository->getProject()->getID()
        );

        $repo_event['system_path'] = $svn_repository->getSystemPath();
        $repo_event['project_id']  = $svn_repository->getProject()->getId();
        $repo_event['name']        = $svn_repository->getProject()->getUnixNameMixedCase() .
            "/" . $svn_repository->getName();

        return $this->system_event_manager->createEvent(
            'Tuleap\\Svn\\EventRepository\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            implode(SystemEvent::PARAMETER_SEPARATOR, $repo_event),
            SystemEvent::PRIORITY_HIGH
        );
    }
}
