<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'SystemEventManager.class.php';
require_once 'common/backend/BackendAliases.class.php';
require_once 'common/backend/BackendCVS.class.php';
require_once 'common/backend/BackendSVN.class.php';
require_once 'common/backend/BackendSystem.class.php';

class SystemEventProcessor {

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var SystemEventDao
     */
    private $dao;

    /**
     * @var BackendAliases
     */
    private $backend_aliases;

    /**
     * @var BackendCVS
     */
    private $backend_cvs;

    /**
     * @var BackendSVN
     */
    private $backend_svn;

    /**
     * @var BackendSystem
     */
    private $backend_system;

    public function __construct(
            SystemEventManager $system_event_manager,
            SystemEventDao     $dao,
            BackendAliases     $backend_aliases,
            BackendCVS         $backend_cvs,
            BackendSVN         $backend_svn,
            BackendSystem      $backend_system) {
        $this->system_event_manager = $system_event_manager;
        $this->dao             = $dao;
        $this->backend_aliases = $backend_aliases;
        $this->backend_cvs     = $backend_cvs;
        $this->backend_svn     = $backend_svn;
        $this->backend_system  = $backend_system;

    }

    /**
     * Process stored events.
     */
    public function process() {
        while (($dar=$this->dao->checkOutNextEvent()) != null) {
            if ($row = $dar->getRow()) {
                //echo "Processing event ".$row['id']." (".$row['type'].")\n";
                $sysevent = $this->system_event_manager->getInstanceFromRow($row);
                // Process $sysevent
                if ($sysevent) {
                    $this->backend_system->log("Processing event #".$sysevent->getId()." ".$sysevent->getType()."(".$sysevent->getParameters().")", Backend::LOG_INFO);
                    try {
                        $sysevent->process();
                    } catch (Exception $exception) {
                        $sysevent->logException($exception);
                    }
                    $this->dao->close($sysevent);
                    $sysevent->notify();
                    $this->backend_system->log("Processing event #".$sysevent->getId().": done.", Backend::LOG_INFO);
                    // Output errors???
                }
            }
        }
        // Since generating aliases may be costly, do it only once everything else is processed
        if ($this->backend_aliases->aliasesNeedUpdate()) {
            $this->backend_aliases->update();
        }

        // Update CVS root allow file once everything else is processed
        if ($this->backend_cvs->getCVSRootListNeedUpdate()) {
            $this->backend_cvs->CVSRootListUpdate();
        }

        // Update SVN root definition for Apache once everything else is processed
        if ($this->backend_svn->getSVNApacheConfNeedUpdate()) {
            $this->backend_svn->generateSVNApacheConf();
            // Need to refresh apache (graceful)
            system('/sbin/service httpd graceful');
        }
        // Update system user and group caches once everything else is processed
        if ($this->backend_system->getNeedRefreshUserCache()) {
            $this->backend_system->refreshUserCache();
        }
        if ($this->backend_system->getNeedRefreshGroupCache()) {
            $this->backend_system->refreshGroupCache();
        }
    }
}

?>
