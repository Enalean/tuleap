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

require_once '/usr/share/php-guzzle/guzzle.phar';

require_once dirname(__FILE__).'/../GerritTestInterfaces.php';

abstract class Git_Driver_GerritREST_base extends TuleapTestCase {

    protected $temporary_file_for_body = "a php resource to a file";

    protected $project_name    = 'fire/fox';
    protected $namespace       = 'jean-claude';
    protected $repository_name = 'dusse';

    protected $gerrit_project_name = 'fire/fox/jean-claude/dusse';

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

    protected $guzzle_client;

    protected $guzzle_request;

    protected $logger;

    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->logger        = mock('BackendLogger');

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

        $this->guzzle_client  = mock('Guzzle\Http\Client');
        $this->guzzle_request = mock('Guzzle\Http\Message\EntityEnclosingRequest');

        $this->driver = new Git_Driver_GerritREST($this->guzzle_client, $this->logger, 'Digest');
    }

    protected function getGuzzleRequestWithTextResponse($text) {
        $response     = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($text);
        return stub('Guzzle\Http\Message\EntityEnclosingRequest')->send()->returns($response);
    }
}