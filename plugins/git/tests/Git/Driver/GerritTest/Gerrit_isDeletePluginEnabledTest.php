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

interface Git_Driver_Gerrit_isDeletePluginEnabledTest {
    public function itReturnsFalseIfPluginIsNotInstalled();
    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled();
    public function itReturnsTrueIfPluginIsInstalledAndEnabled();
}

class Git_Driver_GerritLegacy_isDeletePluginEnabledTest extends TuleapTestCase implements Git_Driver_Gerrit_isDeletePluginEnabledTest {
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

    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_GerritLegacy($this->ssh, $this->logger);
    }

    public function itReturnsFalseIfPluginIsNotInstalled() {
        stub($this->ssh)->execute()->returns("");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled() {
        stub($this->ssh)->execute()->returns("Name                           Version    Status
                                        ----------------------------------------------------
                                        deleteproject                  1.1-SNAPSHOT DISABLED
                                        replication                    1.0        ENABLED");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsTrueIfPluginIsInstalledAndEnabled() {
        stub($this->ssh)->execute()->returns("Name                           Version    Status
                                        ----------------------------------------------------
                                        deleteproject                  1.1-SNAPSHOT ENABLED
                                        replication                    1.0        ENABLED");
        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertTrue($enabled);
    }
}

class Git_DriverREST_Gerrit_isDeletePluginEnabledTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_isDeletePluginEnabledTest {

    private $response_with_plugin;
    private $response_without_plugin;

    public function setUp() {
        parent::setUp();
        $this->response_with_plugin = <<<EOS
)]}'
{
  "deleteproject": {
    "kind": "gerritcodereview#plugin",
    "id": "deleteproject",
    "version": "v2.8.2"
  },
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
        $this->response_without_plugin = <<<EOS
)]}'
{
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
    }
    public function itReturnsFalseIfPluginIsNotInstalled(){
        stub($this->http_client)->getLastResponse()->returns($this->response_without_plugin);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled(){
        stub($this->http_client)->getLastResponse()->returns($this->response_without_plugin);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsTrueIfPluginIsInstalledAndEnabled(){
        stub($this->http_client)->getLastResponse()->returns($this->response_with_plugin);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertTrue($enabled);
    }

    public function itCallsGerritServerWithOptions() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/plugins/';

        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'GET'
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($options)->once();

        $this->driver->isDeletePluginEnabled($this->gerrit_server);
    }
}