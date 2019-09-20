<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class Git_Driver_Gerrit_ProjectCreatorStatus
{

    /** @var Git_Driver_Gerrit_ProjectCreatorStatusDao */
    private $dao;

    /** @var array */
    private $cache = array();

    public const ERROR = 'ERROR';
    public const QUEUE = 'QUEUE';
    public const DONE  = 'DONE';

    public function __construct(Git_Driver_Gerrit_ProjectCreatorStatusDao $dao)
    {
        $this->dao = $dao;
    }

    public function getStatus(GitRepository $repository)
    {
        $event_status = $this->getEventStatus($repository);
        if (! $repository->isMigratedToGerrit()) {
            if ($event_status == SystemEvent::STATUS_NEW) {
                return self::QUEUE;
            }
            return null;
        }
        if ($repository->getMigrationStatus() != null) {
            return $repository->getMigrationStatus();
        }
        switch ($event_status) {
            case SystemEvent::STATUS_RUNNING:
                return self::QUEUE;

            default:
                return self::DONE;
        }
    }

    private function getEventStatus(GitRepository $repository)
    {
        $row = $this->getEvent($repository);
        if ($row) {
            return $row['status'];
        }
        return null;
    }

    public function getEventDate(GitRepository $repository)
    {
        $row = $this->getEvent($repository);
        if ($row) {
            return $row['create_date'];
        }
    }

    public function getLog(GitRepository $repository)
    {
        $row = $this->getEvent($repository);
        if ($row) {
            return $row['log'];
        }
    }

    private function getEvent(GitRepository $repository)
    {
        if (! isset($this->cache[$repository->getId()])) {
            $this->cache[$repository->getId()] = $this->dao->getSystemEventForRepository($repository->getId());
        }
        return $this->cache[$repository->getId()];
    }

    public function canModifyPermissionsTuleapSide(GitRepository $repository)
    {
        $status = $this->getStatus($repository);
        if ($status == Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE || $status == Git_Driver_Gerrit_ProjectCreatorStatus::DONE) {
            return false;
        }
        return true;
    }
}
