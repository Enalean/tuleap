<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

require_once '/usr/share/php/Guzzle/autoload.php';
require_once __DIR__.'/../../../../bootstrap.php';

class Git_DriverREST_Gerrit_manageProjectsTest extends TuleapTestCase
{

    protected $logger;
    protected $gerrit_server_host = 'http://gerrit.example.com';
    /** @var Project */
    protected $project;
    protected $guzzle_request;
    protected $project_name = 'fire/fox';
    /** @var GitRepository */
    protected $repository;
    protected $gerrit_server_port = 8080;
    protected $temporary_file_for_body = "a php resource to a file";
    /** @var Git_Driver_GerritREST */
    protected $driver;
    protected $gerrit_project_name = 'fire/fox/jean-claude/dusse';
    protected $namespace = 'jean-claude';
    protected $gerrit_server_user = 'admin-tuleap.example.com';
    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;
    protected $gerrit_server_pass = 'correct horse battery staple';
    protected $repository_name = 'dusse';
    protected $guzzle_client;

    public function setUp()
    {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->logger        = mock('BackendLogger');

        stub($this->gerrit_server)->getHost()->returns($this->gerrit_server_host);
        stub($this->gerrit_server)->getHTTPPassword()->returns($this->gerrit_server_pass);
        stub($this->gerrit_server)->getLogin()->returns($this->gerrit_server_user);
        stub($this->gerrit_server)->getHTTPPort()->returns($this->gerrit_server_port);
        stub($this->gerrit_server)->getBaseUrl()->returns($this->gerrit_server_host . ':' . $this->gerrit_server_port);

        $this->project    = stub('Project')->getUnixName()->returns($this->project_name);
        $this->repository = aGitRepository()
            ->withProject($this->project)
            ->withNamespace($this->namespace)
            ->withName($this->repository_name)
            ->build();

        $this->guzzle_client  = mock('Guzzle\Http\Client');
        $this->guzzle_request = mock('Guzzle\Http\Message\EntityEnclosingRequest');

        $this->driver = new Git_Driver_GerritREST($this->guzzle_client, $this->logger, 'Digest');
    }

    protected function getGuzzleRequestWithTextResponse($text)
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($text);
        return stub('Guzzle\Http\Message\EntityEnclosingRequest')->send()->returns($response);
    }

    public function itExecutesTheCreateCommandForProjectOnTheGerritServer()
    {
        $url_create_project = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->gerrit_project_name);

        $expected_json_data = json_encode(
            array(
                'description' => "Migration of $this->gerrit_project_name from Tuleap",
                'parent'      => $this->project_name
            )
        );

        expect($this->guzzle_client)->put(
            $url_create_project,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
    }

    public function itExecutesTheCreateCommandForParentProjectOnTheGerritServer()
    {
        $url_create_project = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name);

        $expected_json_data = json_encode(
            array(
                'description'      => "Migration of $this->project_name from Tuleap",
                'permissions_only' => true,
                'owners'           => array(
                    'firefox/project_admins'
                )
            )
        );

        expect($this->guzzle_client)->put(
            $url_create_project,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->createProjectWithPermissionsOnly($this->gerrit_server, $this->project, 'firefox/project_admins');
    }

    public function itReturnsTheNameOfTheCreatedProject()
    {
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $project_name = $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
        $this->assertEqual($project_name, $this->gerrit_project_name);
    }

    public function itRaisesAGerritDriverExceptionOnProjectCreation()
    {
        stub($this->guzzle_client)->put()->throws(new Guzzle\Http\Exception\ClientErrorResponseException());

        $this->expectException('Git_Driver_Gerrit_Exception');

        expect($this->logger)->error()->once();

        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
    }

    public function itDoesntTransformExceptionsThatArentRelatedToGerrit()
    {
    }
    public function itInformsAboutProjectInitialization()
    {
    }

    public function itPutsThneProjectInReadOnly()
    {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name) .'/config';

        $expected_json_data = json_encode(
            array(
                'state' => 'READ_ONLY'
            )
        );

        expect($this->guzzle_client)->put(
            $url,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->makeGerritProjectReadOnly($this->gerrit_server, $this->project_name);
    }

    public function itAddsTheProjectInheritance()
    {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name) .'/parent';

        $expected_json_data = json_encode(
            array(
                'parent' => 'prj'
            )
        );

        expect($this->guzzle_client)->put(
            $url,
            array(
                Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
                'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->setProjectInheritance($this->gerrit_server, $this->project_name, 'prj');
    }

    public function itResetsTheProjectInheritance()
    {
        $url = $this->gerrit_server_host
           .':'. $this->gerrit_server_port
           .'/a/projects/'. urlencode($this->project_name) .'/parent';

        $expected_json_data = json_encode(
            array(
               'parent' => Git_Driver_Gerrit::DEFAULT_PARENT_PROJECT
            )
        );

        expect($this->guzzle_client)->put(
            $url,
            array(
               Git_Driver_GerritREST::HEADER_CONTENT_TYPE => Git_Driver_GerritREST::MIME_JSON,
               'verify' => false,
            ),
            $expected_json_data
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->resetProjectInheritance($this->gerrit_server, $this->project_name);
    }

    public function itDeletesProject()
    {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name);

        expect($this->guzzle_client)->delete(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->delete()->returns($this->guzzle_request);

        expect($this->logger)->info()->count(2);

        $this->driver->deleteProject($this->gerrit_server, $this->project_name);
    }
}
