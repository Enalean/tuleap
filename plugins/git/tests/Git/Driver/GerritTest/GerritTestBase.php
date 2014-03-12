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

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once 'common/include/Config.class.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAP_User.class.php';

abstract class Git_Driver_GerritLegacy_baseTest extends TuleapTestCase {

    /**
     * @var GitRepository
     */
    protected $repository;

    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;

    /** @var Project */
    protected $project;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    /** @var Git_Driver_GerritLegacy */
    protected $driver;

    public function setUp() {
        parent::setUp();

        $this->project_name    = 'firefox';
        $this->namespace       = 'jean-claude';
        $this->repository_name = 'dusse';

        $this->project = stub('Project')->getUnixName()->returns($this->project_name);

        $this->repository = aGitRepository()
            ->withProject($this->project)
            ->withNamespace($this->namespace)
            ->withName($this->repository_name)
            ->build();

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_GerritLegacy($this->ssh, $this->logger);
    }

}
abstract class Git_Driver_GerritREST_baseTest extends TuleapTestCase {

    protected $temporary_file_for_body = "a php resource to a file";

    protected $project_name    = 'fire/fox';
    protected $namespace       = 'jean-claude';
    protected $repository_name = 'dusse';

    protected $gerrit_project_name = 'fire/fox/jean-claude/dusse';

    /** @var Http_Client */
    protected $http_client;
    protected $gerrit_server_host = 'http://gerrit.example.com';
    protected $gerrit_server_port = 8080;
    protected $gerrit_server_pass = 'correct horse battery staple';
    protected $gerrit_server_user = 'admin-tuleap.example.com';

    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;

    /** @var Git_Driver_GerritREST */
    protected $driver;

    /** @var GitRepository */
    protected $repository;

    /** @var Project */
    protected $project;

    /** @var Git_Driver_GerritRESTBodyBuilder */
    protected $body_builder;

    public function setUp() {
        parent::setUp();
        $this->http_client   = mock('Http_Client');
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->logger        = mock('BackendLogger');
        $this->body_builder  = mock('Git_Driver_GerritRESTBodyBuilder');

        stub($this->body_builder)->getTemporaryFile()->returns($this->temporary_file_for_body);

        stub($this->gerrit_server)->getHost()->returns($this->gerrit_server_host);
        stub($this->gerrit_server)->getHTTPPassword()->returns($this->gerrit_server_pass);
        stub($this->gerrit_server)->getLogin()->returns($this->gerrit_server_user);
        stub($this->gerrit_server)->getHTTPPort()->returns($this->gerrit_server_port);
        stub($this->gerrit_server)->getBaseUrl()->returns($this->gerrit_server_host .':'. $this->gerrit_server_port);

        $this->project = stub('Project')->getUnixName()->returns($this->project_name);
        $this->repository = aGitRepository()
            ->withProject($this->project)
            ->withNamespace($this->namespace)
            ->withName($this->repository_name)
            ->build();

        $this->driver = new Git_Driver_GerritREST($this->http_client, $this->logger, $this->body_builder);
    }
}