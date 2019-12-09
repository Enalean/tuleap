<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ProjectCreatorCallToGerritTest extends TuleapTestCase
{
    protected $contributors      = 'tuleap-localhost-mozilla/firefox-contributors';
    protected $integrators       = 'tuleap-localhost-mozilla/firefox-integrators';
    protected $supermen          = 'tuleap-localhost-mozilla/firefox-supermen';
    protected $owners            = 'tuleap-localhost-mozilla/firefox-owners';
    protected $replication       = 'tuleap.example.com-replication';

    protected $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $owners_uuid       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';
    protected $replication_uuid  = '2ce5c45e3b88415e51ce7e0d3a1ba0526dce6424';

    protected $project_members;
    protected $another_ugroup;
    protected $project_admins;
    protected $project_members_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $another_ugroup_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $project_admins_uuid  = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $project_members_gerrit_name = 'mozilla/project_members';
    protected $another_ugroup_gerrit_name  = 'mozilla/another_ugroup';
    protected $project_admins_gerrit_name  = 'mozilla/project_admins';

    protected $tmpdir;
    protected $gerrit_tmpdir;
    protected $fixtures;

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    /** @var Project */
    protected $project;
    protected $project_id = 103;
    protected $project_unix_name = 'mozilla';

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var Git_Driver_Gerrit_UmbrellaProjectManager */
    protected $umbrella_manager;

    protected $gerrit_project = 'tuleap-localhost-mozilla/firefox';
    protected $gerrit_git_url;
    protected $gerrit_admin_instance = 'admin-tuleap.example.com';
    protected $tuleap_instance       = 'tuleap.example.com';
    protected $gitolite_project = 'gitolite_firefox.git';

    /** @var Git_Driver_Gerrit_Template_TemplateFactory */
    protected $template_factory;

    protected $template_id = 'default';

    protected $template;
    protected $gerrit_driver_factory;

    /** @var Git_Driver_Gerrit_Template_TemplateProcessor */
    protected $template_processor;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_default_domain', $this->tuleap_instance);
        ForgeConfig::set('tmp_dir', '/var/tmp');
        $this->fixtures = dirname(__FILE__) .'/_fixtures';
        do {
            $this->tmpdir   = ForgeConfig::get('tmp_dir') .'/'. md5(uniqid(rand(), true));
        } while (is_dir($this->tmpdir));
        `unzip $this->fixtures/firefox.zip -d $this->tmpdir`;
        `tar -xzf $this->fixtures/gitolite_firefox.git.tgz --directory $this->tmpdir`;

        $host  = $this->tmpdir;
        $login = $this->gerrit_admin_instance;
        $id = $ssh_port = $http_port = $identity_file = $replication_key = $use_ssl = $gerrit_version = $http_password = $auth_type = 0;
        $this->server = partial_mock(
            'Git_RemoteServer_GerritServer',
            array('getCloneSSHUrl'),
            array(
                $id,
                $host,
                $ssh_port,
                $http_port,
                $login,
                $identity_file,
                $replication_key,
                $use_ssl,
                $gerrit_version,
                $http_password,
                '',
                $auth_type
            )
        );

        $this->gerrit_git_url = "$host/$this->gerrit_project";
        stub($this->server)->getCloneSSHUrl($this->gerrit_project)->returns($this->gerrit_git_url);

        $this->migrate_access_rights = true;
        $this->project               = mock('Project');
        stub($this->project)->getUnixName()->returns($this->project_unix_name);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->getID()->returns($this->project_id);
        $private_project = stub('Project')->isPublic()->returns(false);

        $this->repository                      = mock('GitRepository');
        stub($this->repository)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_in_a_private_project = mock('GitRepository');
        stub($this->repository_in_a_private_project)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_without_registered   = mock('GitRepository');
        stub($this->repository_without_registered)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_with_registered   = mock('GitRepository');
        stub($this->repository_with_registered)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);

        $this->driver = mock('Git_Driver_Gerrit');
        stub($this->driver)->createProject($this->server, $this->repository, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_in_a_private_project, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_without_registered, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_with_registered, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProjectWithPermissionsOnly($this->server, $this->project, $this->project_admins_gerrit_name)->returns($this->project_unix_name);
        stub($this->driver)->doesTheProjectExist()->returns(false);
        stub($this->driver)->getGerritProjectName()->returns($this->gerrit_project);

        $this->gerrit_driver_factory = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($this->driver);

        $this->membership_manager = mock('Git_Driver_Gerrit_MembershipManager');
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->contributors)->returns($this->contributors_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->integrators)->returns($this->integrators_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->supermen)->returns($this->supermen_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->owners)->returns($this->owners_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->replication)->returns($this->replication_uuid);

        $this->userfinder = mock('Git_Driver_Gerrit_UserFinder');
        $this->ugroup_manager = mock('UGroupManager');

        $this->project_manager = mock('ProjectManager');

        $this->umbrella_manager = mock('Git_Driver_Gerrit_UmbrellaProjectManager');

        $this->template           = stub('Git_Driver_Gerrit_Template_Template')->getId()->returns(12);
        $this->template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $this->template_factory   = stub('Git_Driver_Gerrit_Template_TemplateFactory')->getTemplate(12)->returns($this->template);
        stub($this->template_factory)->getTemplatesAvailableForRepository()->returns(array($this->template));

        $this->gerrit_tmpdir = $this->tmpdir.'/gerrit_tbd';

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $this->gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $this->getGitExec($this->gerrit_tmpdir)
        );

        stub($this->repository)->getProject()->returns($this->project);
        stub($this->repository_in_a_private_project)->getProject()->returns($private_project);
        stub($this->repository_without_registered)->getProject()->returns($this->project);
        stub($this->repository_with_registered)->getProject()->returns($this->project);

        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository_in_a_private_project)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository_without_registered)->returns(false);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository_with_registered)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_WRITE, $this->repository_with_registered)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_WPLUS, $this->repository_with_registered)->returns(true);

        stub($this->userfinder)->getUgroups()->returns(array());
    }

    private function getGitExec($dir)
    {
        $git_exec = new Git_Exec($dir);
        $git_exec->allowUsageOfFileProtocol();
        return $git_exec;
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
        $this->recurseDeleteInDir($this->tmpdir);
        rmdir($this->tmpdir);
    }

    public function itCreatesAProjectAndExportGitBranchesAndTagsWithoutCreateParentProject()
    {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile

        $this->project_admins = mock('ProjectUGroup');
        stub($this->project_admins)->getNormalizedName()->returns('project_admins');
        stub($this->project_admins)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);

        stub($this->ugroup_manager)->getUGroups()->returns(array($this->project_admins));
        stub($this->driver)->DoesTheParentProjectExist()->returns(true);

        stub($this->membership_manager)->createArrayOfGroupsForServer()->returns(array($this->project_admins));

        expect($this->umbrella_manager)->recursivelyCreateUmbrellaProjects(array($this->server), $this->project)->once();
        expect($this->driver)->createProject($this->server, $this->repository, $this->project_unix_name)->once();

        $project_name = $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
        $this->assertEqual($this->gerrit_project, $project_name);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function itCreatesProjectMembersGroup()
    {
        $ugroup = mock('ProjectUGroup');
        stub($ugroup)->getNormalizedName()->returns('project_members');
        stub($ugroup)->getId()->returns(ProjectUGroup::PROJECT_MEMBERS);

        $ugroup_project_admins = mock('ProjectUGroup');
        stub($ugroup_project_admins)->getNormalizedName()->returns('project_admins');
        stub($ugroup_project_admins)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);

        expect($this->ugroup_manager)->getUGroups($this->project)->once();
        stub($this->ugroup_manager)->getUGroups()->returns(array($ugroup, $ugroup_project_admins));

        stub($this->membership_manager)->createArrayOfGroupsForServer()->returns(array($ugroup, $ugroup_project_admins));

        expect($this->membership_manager)->createArrayOfGroupsForServer($this->server, array($ugroup, $ugroup_project_admins))->once();
        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function itCreatesAllGroups()
    {
        $ugroup_project_members = mock('ProjectUGroup');
        stub($ugroup_project_members)->getNormalizedName()->returns('project_members');
        stub($ugroup_project_members)->getId()->returns(ProjectUGroup::PROJECT_MEMBERS);

        $ugroup_project_admins = mock('ProjectUGroup');
        stub($ugroup_project_admins)->getNormalizedName()->returns('project_admins');
        stub($ugroup_project_admins)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);

        $ugroup_another_group = mock('ProjectUGroup');
        stub($ugroup_another_group)->getNormalizedName()->returns('another_group');
        stub($ugroup_another_group)->getId()->returns(120);

        stub($this->ugroup_manager)->getUGroups()->returns(array($ugroup_project_members, $ugroup_another_group, $ugroup_project_admins));

        expect($this->membership_manager)->createArrayOfGroupsForServer($this->server, array($ugroup_project_members, $ugroup_another_group, $ugroup_project_admins))->once();
        stub($this->membership_manager)->createArrayOfGroupsForServer()->returns(array($ugroup_project_members, $ugroup_another_group, $ugroup_project_admins));

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    private function assertAllGitBranchesPushedToTheServer()
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec("git show-ref --heads", $refs_cmd, $ret_val);

        $expected_result = array("To $this->gerrit_git_url");

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec("git push $this->gerrit_git_url refs/heads/*:refs/heads/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEqual($output, $expected_result);
        $this->assertEqual($ret_val, 0);
    }

    private function assertAllGitTagsPushedToTheServer()
    {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec("git show-ref --tags", $refs_cmd, $ret_val);
        $expected_result = array("To $this->gerrit_git_url");

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec("git push $this->gerrit_git_url refs/tags/*:refs/tags/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEqual($output, $expected_result);
        $this->assertEqual($ret_val, 0);
    }
}
