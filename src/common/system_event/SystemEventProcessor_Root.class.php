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

use Tuleap\SVNCore\ApacheConfGenerator;

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
        BackendSVN $backend_svn,
        BackendSystem $backend_system,
        SiteCache $site_cache,
        ApacheConfGenerator $generator,
    ) {
        parent::__construct($process, $system_event_manager, $dao, $logger);
        $this->backend_aliases = $backend_aliases;
        $this->backend_svn     = $backend_svn;
        $this->backend_system  = $backend_system;
        $this->site_cache      = $site_cache;
        $this->generator       = $generator;
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

        // Update SVN root definition for Apache once everything else is processed
        if ($this->backend_svn->getSVNApacheConfNeedUpdate()) {
            $this->generator->generate();
        }

        $this->triggerApplicationOwnerEventsProcessing();
    }

    protected function triggerApplicationOwnerEventsProcessing()
    {
        $app     = new SystemEventProcessor_ApplicationOwner(new SystemEventProcessApplicationOwnerDefaultQueue(), $this->system_event_manager, $this->dao, $this->logger);
        $command = sprintf('/usr/bin/tuleap %s %s', \Tuleap\CLI\Command\ProcessSystemEventsCommand::NAME, SystemEvent::OWNER_APP);
        $this->launchAs($app->getProcessOwner(), $command);
    }

    protected function launchAs(string $user, string $command): void
    {
        $cmd     = 'sudo -E -u ' . $user . ' -- ' . $command;
        $process = Symfony\Component\Process\Process::fromShellCommandline($cmd);
        $process->start();

        $output = '';

        foreach ($process as $type => $data) {
            $output .= $data;
        }

        if (! $process->isSuccessful()) {
            throw new Exception('Unable to run command "' . $command . '" (error code: ' . $process->getExitCode() . '): ' . $output);
        }
    }

    public function getProcessOwner()
    {
        return 'root';
    }
}
