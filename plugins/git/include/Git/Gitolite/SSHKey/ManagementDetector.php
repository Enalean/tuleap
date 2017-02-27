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

namespace Tuleap\Git\Gitolite\SSHKey;

use SystemEventManager;
use Tuleap\Git\Gitolite\SSHKey\SystemEvent\MigrateToTuleapSSHKeyManagement;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\GlobalParameterDao;

class ManagementDetector
{
    /**
     * @var VersionDetector
     */
    private $version_detector;
    /**
     * @var GlobalParameterDao
     */
    private $global_parameter_dao;
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    public function __construct(
        VersionDetector $version_detector,
        GlobalParameterDao $global_parameter_dao,
        SystemEventManager $system_event_manager
    ) {
        $this->version_detector     = $version_detector;
        $this->global_parameter_dao = $global_parameter_dao;
        $this->system_event_manager = $system_event_manager;
    }

    /**
     * @return bool
     */
    public function isAuthorizedKeysFileManagedByTuleap()
    {
        if (! $this->version_detector->isGitolite3()) {
            return false;
        }

        return $this->global_parameter_dao->isAuthorizedKeysFileManagedByTuleap();
    }

    /**
     * @return bool
     */
    public function canRequestAuthorizedKeysFileManagementByTuleap()
    {
        if (! $this->version_detector->isGitolite3()) {
            return false;
        }

        if ($this->global_parameter_dao->isAuthorizedKeysFileManagedByTuleap()) {
            return false;
        }

        return ! $this->system_event_manager->isThereAnEventAlreadyOnGoing(
            MigrateToTuleapSSHKeyManagement::NAME
        );
    }
}
