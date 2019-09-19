<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/GerritTestBase.php';

class Git_Driver_GerritLegacy_DeletePluginTest extends TuleapTestCase implements Git_Driver_Gerrit_DeletePluginTest
{
    /**
     * @var Git_Driver_Gerrit
     */
    private $driver;

    /**
     * @var Git_RemoteServer_GerritServer
     */
    private $gerrit_server;

    /**
     * @var Git_Driver_Gerrit_RemoteSSHCommand
     */
    private $ssh;

    public function setUp()
    {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_GerritLegacy($this->ssh, $this->logger);
    }

    public function itReturnsFalseIfPluginIsNotInstalled()
    {
        stub($this->ssh)->execute()->returns("");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled()
    {
        stub($this->ssh)->execute()->returns("Name                           Version    Status
                                        ----------------------------------------------------
                                        deleteproject                  1.1-SNAPSHOT DISABLED
                                        replication                    1.0        ENABLED");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsTrueIfPluginIsInstalledAndEnabled()
    {
        stub($this->ssh)->execute()->returns("Name                           Version    Status
                                        ----------------------------------------------------
                                        deleteproject                  1.1-SNAPSHOT ENABLED
                                        replication                    1.0        ENABLED");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertTrue($enabled);
    }

    public function itThrowsAProjectDeletionExceptionIfThereAreOpenChanges()
    {
        $exception = new Git_Driver_Gerrit_RemoteSSHCommandFailure(1, '', 'error');
        stub($this->ssh)->execute()->throws($exception);

        $this->expectException('ProjectDeletionException');

        $this->driver->deleteProject($this->gerrit_server, 'project');
    }
}
