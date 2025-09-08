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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\SystemEvent\RootPostEventsActionsEvent;

class SystemEventProcessor_Root extends SystemEventProcessor // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(
        SystemEventProcess $process,
        SystemEventManager $system_event_manager,
        SystemEventDao $dao,
        \Psr\Log\LoggerInterface $logger,
        private readonly BackendAliases $backend_aliases,
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly SiteCache $site_cache,
    ) {
        parent::__construct($process, $system_event_manager, $dao, $logger);
    }

    #[\Override]
    public function getOwner()
    {
        return SystemEvent::OWNER_ROOT;
    }

    #[\Override]
    protected function postEventsActions(array $executed_events_ids, $queue_name)
    {
        $this->site_cache->restoreOwnership();

         // Since generating aliases may be costly, do it only once everything else is processed
        if ($this->backend_aliases->aliasesNeedUpdate()) {
            $this->backend_aliases->update();
        }

        $this->event_dispatcher->dispatch(new RootPostEventsActionsEvent());

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
        $process = Symfony\Component\Process\Process::fromShellCommandline($cmd, '/');
        $process->start();

        $output = '';

        foreach ($process as $type => $data) {
            $output .= $data;
        }

        if (! $process->isSuccessful()) {
            throw new Exception('Unable to run command "' . $command . '" (error code: ' . $process->getExitCode() . '): ' . $output);
        }
    }

    #[\Override]
    public function getProcessOwner()
    {
        return 'root';
    }
}
