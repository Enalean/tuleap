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

interface Git_Driver_Gerrit_manageProjectsTest {
    public function itExecutesTheCreateCommandForProjectOnTheGerritServer();
    public function itExecutesTheCreateCommandForParentProjectOnTheGerritServer();
    public function itReturnsTheNameOfTheCreatedProject();
    public function itRaisesAGerritDriverExceptionOnProjectCreation();
    public function itDoesntTransformExceptionsThatArentRelatedToGerrit();
    public function itInformsAboutProjectInitialization();
}

class Git_Driver_GerritLegacy_manageProjectsTest extends Git_Driver_GerritLegacy_baseTest implements Git_Driver_Gerrit_manageProjectsTest {
    /**
     * @var GitRepository
     */
    protected $repository;

    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    public function itExecutesTheCreateCommandForProjectOnTheGerritServer() {
        expect($this->ssh)->execute($this->gerrit_server, "gerrit create-project --parent firefox firefox/jean-claude/dusse")->once();
        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
    }

    public function itExecutesTheCreateCommandForParentProjectOnTheGerritServer() {
        expect($this->ssh)->execute($this->gerrit_server, "gerrit create-project --permissions-only firefox --owner firefox/project_admins")->once();
        $this->driver->createProjectWithPermissionsOnly($this->gerrit_server, $this->project, 'firefox/project_admins');
    }

    public function itReturnsTheNameOfTheCreatedProject() {
        $project_name = $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
        $this->assertEqual($project_name, "firefox/jean-claude/dusse");
    }

    public function _itCallsTheRealThing() {
        $r = new GitRepository();
        $r->setName('dusse');
        $r->setNamespace('jean_claude');
        //$p = new Project(array('unix_group_name' => 'LesBronzes', 'group_id' => 50));
        $p = stub('Project' )->getUnixName()->returns('LesBronzes');
        $r->setProject($p);

        $driver = new Git_Driver_Gerrit(new Git_Driver_Gerrit_RemoteSSHCommand(new BackendLogger()), new BackendLogger());
        $driver->createProject($r);
    }

    public function itRaisesAGerritDriverExceptionOnProjectCreation() {
        $std_err = 'fatal: project "someproject" exists';
        $command = "gerrit create-project --parent firefox firefox/jean-claude/dusse";
        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_GerritLegacy::EXIT_CODE, '', $std_err));
        try {
            $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(),"Command: $command".PHP_EOL."Error: $std_err");
        }
    }

    public function itDoesntTransformExceptionsThatArentRelatedToGerrit() {
        $std_err = 'some gerrit exception';
        $this->expectException('Git_Driver_Gerrit_RemoteSSHCommandFailure');
        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(255,'',$std_err));
        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
    }

    public function itInformsAboutProjectInitialization() {
        $remote_project = "firefox/jean-claude/dusse";
        expect($this->logger)->info("Gerrit: Project $remote_project successfully initialized")->once();
        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);

    }
}

class Git_DriverREST_Gerrit_manageProjectsTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_manageProjectsTest {

    public function itExecutesTheCreateCommandForProjectOnTheGerritServer(){
        stub($this->http_client)->isLastResponseSuccess()->returns(true);

        $url_create_project = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->gerrit_project_name);

        $expected_json_data = json_encode(
            array(
                'description' => "Migration of $this->gerrit_project_name from Tuleap",
                'parent'      => $this->project_name
            )
        );

        $expected_options_create_project = array(
            CURLOPT_URL             => $url_create_project,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options_create_project)->once();

        $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
    }

    public function itExecutesTheCreateCommandForParentProjectOnTheGerritServer(){
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

        $expected_options_create_project = array(
            CURLOPT_URL             => $url_create_project,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options_create_project)->once();

        $this->driver->createProjectWithPermissionsOnly($this->gerrit_server, $this->project, 'firefox/project_admins');
    }

    public function itReturnsTheNameOfTheCreatedProject(){
        stub($this->http_client)->isLastResponseSuccess()->returns(true);

        $project_name = $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
        $this->assertEqual($project_name, $this->gerrit_project_name);
    }

    public function itRaisesAGerritDriverExceptionOnProjectCreation(){
         $url_create_project = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->gerrit_project_name);

        stub($this->http_client)->isLastResponseSuccess()->returns(false);
        stub($this->http_client)->getLastHTTPCode()->returns(404);

        try {
            $this->driver->createProject($this->gerrit_server, $this->repository, $this->project_name);
        } catch(Git_Driver_Gerrit_Exception $exception) {
            $this->assertEqual(
                $exception->getMessage(),
                'url: '. $url_create_project.', http code: '. $this->http_client->getLastHTTPCode()
            );
        }
    }

    public function itDoesntTransformExceptionsThatArentRelatedToGerrit(){}
    public function itInformsAboutProjectInitialization(){}

    public function itPutsThneProjectInReadOnly() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name) .'/config';

        $expected_json_data = json_encode(
            array(
                'state' => 'READ_ONLY'
            )
        );

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->driver->makeGerritProjectReadOnly($this->gerrit_server, $this->project_name);
    }

    public function itAddsTheProjectInheritance() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name) .'/parent';

        $expected_json_data = json_encode(
            array(
                'parent' => 'prj'
            )
        );

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->driver->setProjectInheritance($this->gerrit_server, $this->project_name, 'prj');
    }

     public function itResetsTheProjectInheritance() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name) .'/parent';

        $expected_json_data = json_encode(
            array(
                'parent' => Git_Driver_Gerrit::DEFAULT_PARENT_PROJECT
            )
        );

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_PUT             => true,
            CURLOPT_HTTPHEADER      => array(Git_Driver_GerritREST::CONTENT_TYPE_JSON),
            CURLOPT_INFILE          => $this->temporary_file_for_body,
            CURLOPT_INFILESIZE      => strlen($expected_json_data)
        );

        expect($this->body_builder)->getTemporaryFile($expected_json_data)->once();
        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();

        $this->driver->resetProjectInheritance($this->gerrit_server, $this->project_name);
    }

    public function itDeletesProject() {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/projects/'. urlencode($this->project_name);

        $expected_options = array(
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $this->gerrit_server_user .':'. $this->gerrit_server_pass,
            CURLOPT_CUSTOMREQUEST   => 'DELETE'
        );

        expect($this->http_client)->doRequest()->once();
        expect($this->http_client)->addOptions($expected_options)->once();
        expect($this->logger)->info()->count(2);

        $this->driver->deleteProject($this->gerrit_server, $this->project_name);
    }
}