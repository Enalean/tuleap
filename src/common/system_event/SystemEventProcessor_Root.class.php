<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Svn\ApacheConfGenerator;
use Tuleap\Svn\SvnrootUpdater;

class SystemEventProcessor_Root extends SystemEventProcessor
{

    /**
     * @var SiteCache
     */
    private $site_cache;

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
    protected $backend_system;

    /**
     * @var ApacheConfGenerator
     */
    protected $generator;

    public function __construct(
        SystemEventProcess $process,
        SystemEventManager $system_event_manager,
        SystemEventDao $dao,
        \Psr\Log\LoggerInterface $logger,
        BackendAliases $backend_aliases,
        BackendCVS $backend_cvs,
        BackendSVN $backend_svn,
        BackendSystem $backend_system,
        SiteCache $site_cache,
        ApacheConfGenerator $generator
    ) {
        parent::__construct($process, $system_event_manager, $dao, $logger);
        $this->backend_aliases      = $backend_aliases;
        $this->backend_cvs          = $backend_cvs;
        $this->backend_svn          = $backend_svn;
        $this->backend_system       = $backend_system;
        $this->site_cache           = $site_cache;
        $this->generator            = $generator;
    }

    public function getOwner()
    {
        return SystemEvent::OWNER_ROOT;
    }

    protected function postEventsActions(array $executed_events_ids, $queue_name)
    {
        $this->site_cache->restoreOwnership();

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
            $this->generator->generate();
            $updater = new SvnrootUpdater($this->logger);
            $updater->push();
        }

        // Update system user and group caches once everything else is processed
        if ($this->backend_system->getNeedRefreshUserCache()) {
            $this->backend_system->refreshUserCache();
        }
        if ($this->backend_system->getNeedRefreshGroupCache()) {
            $this->backend_system->refreshGroupCache();
        }
        $this->triggerApplicationOwnerEventsProcessing();
    }

    protected function triggerApplicationOwnerEventsProcessing()
    {
        $app = new SystemEventProcessor_ApplicationOwner(new SystemEventProcessApplicationOwnerDefaultQueue(), $this->system_event_manager, $this->dao, $this->logger);
        $command = sprintf('/usr/bin/tuleap %s %s', \Tuleap\CLI\Command\ProcessSystemEventsCommand::NAME, SystemEvent::OWNER_APP);
        $this->launchAs($app->getProcessOwner(), $command);
    }

    protected function launchAs($user, $command)
    {
        $return_val = 0;
        $output = array();
        $cmd    = 'su -l ' . $user . ' -c "' . $command . ' 2>&1"';
        exec($cmd, $output, $return_val);
        if ($return_val == 0) {
            return true;
        } else {
            throw new Exception('Unable to run command "' . $command . '" (error code: ' . $return_val . '): ' . implode("\n", $output));
            return false;
        }
    }

    public function getProcessOwner()
    {
        return 'root';
    }
}
