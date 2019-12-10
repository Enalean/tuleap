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

class Git_Driver_Gerrit_ProjectCreator_InitiatePermissionsTest extends TuleapTestCase
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

    private function getGitExec($dir)
    {
        $git_exec = new Git_Exec($dir);
        $git_exec->allowUsageOfFileProtocol();
        return $git_exec;
    }

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

        $this->project_members = mock('ProjectUGroup');
        stub($this->project_members)->getNormalizedName()->returns('project_members');
        stub($this->project_members)->getId()->returns(ProjectUGroup::PROJECT_MEMBERS);

        $this->another_ugroup = mock('ProjectUGroup');
        stub($this->another_ugroup)->getNormalizedName()->returns('another_ugroup');
        stub($this->another_ugroup)->getId()->returns(120);

        $this->project_admins = mock('ProjectUGroup');
        stub($this->project_admins)->getNormalizedName()->returns('project_admins');
        stub($this->project_admins)->getId()->returns(ProjectUGroup::PROJECT_ADMIN);

        stub($this->ugroup_manager)->getUGroups()->returns(array($this->project_members, $this->another_ugroup, $this->project_admins));

        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->project_members_gerrit_name)->returns($this->project_members_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->another_ugroup_gerrit_name)->returns($this->another_ugroup_uuid);
        stub($this->membership_manager)->getGroupUUIDByNameOnServer($this->server, $this->project_admins_gerrit_name)->returns($this->project_admins_uuid);

        stub($this->membership_manager)->createArrayOfGroupsForServer()->returns(array($this->project_members, $this->another_ugroup, $this->project_admins));
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
        $this->recurseDeleteInDir($this->tmpdir);
        rmdir($this->tmpdir);
    }

    public function itPushesTheUpdatedConfigToTheServer()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::PROJECT_MEMBERS, 120));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(ProjectUGroup::PROJECT_ADMIN));

        $this->project_creator->createGerritProject($this->server, $this->repository, $this->template_id);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertItClonesTheDistantRepo();
        $this->assertCommitterIsConfigured();
        $this->assertTheRemoteOriginIsConfigured();
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        $this->assertEverythingIsCommitted();
        $this->assertEverythingIsPushedToTheServer();
    }

    public function itThrowsAnExceptionIfProjectAlreadyExists()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::PROJECT_MEMBERS, 120));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(ProjectUGroup::PROJECT_ADMIN));

        $driver = mock('Git_Driver_Gerrit');
        stub($driver)->doesTheProjectExist()->returns(true);
        $gerrit_driver_factory = stub('Git_Driver_Gerrit_GerritDriverFactory')->getDriver()->returns($driver);

        $project_creator = new Git_Driver_Gerrit_ProjectCreator(
            $this->gerrit_tmpdir,
            $gerrit_driver_factory,
            $this->userfinder,
            $this->ugroup_manager,
            $this->membership_manager,
            $this->umbrella_manager,
            $this->template_factory,
            $this->template_processor,
            $this->getGitExec($this->gerrit_tmpdir)
        );
        $this->expectException('Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException');

        $project_creator->createGerritProject($this->server, $this->repository, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);
    }

    public function itDoesNotSetPermsIfMigrateAccessRightIsFalse()
    {
        $project_creator = partial_mock(
            Git_Driver_Gerrit_ProjectCreator::class,
            array('exportGitBranches'),
            array($this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            )
        );

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, false);

        $this->assertFileExists("$this->gerrit_tmpdir/project.config");
        $this->assertNoPattern('/group mozilla\//', file_get_contents("$this->gerrit_tmpdir/project.config"));
        $this->assertPattern('/group Administrators/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfProjectIsPrivate()
    {
        $project_creator = partial_mock(
            Git_Driver_Gerrit_ProjectCreator::class,
            array('exportGitBranches'),
            array($this->gerrit_tmpdir,
                $this->gerrit_driver_factory,
                $this->userfinder,
                $this->ugroup_manager,
                $this->membership_manager,
                $this->umbrella_manager,
                $this->template_factory,
                $this->template_processor,
                $this->getGitExec($this->gerrit_tmpdir),
            )
        );

        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $project_creator->createGerritProject($this->server, $this->repository_in_a_private_project, $this->migrate_access_rights);
        $project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertNoPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfRepoHasNoPermsForRegisteredOrAnonymous()
    {
        $groups = array(
            ProjectUGroup::REGISTERED,
            ProjectUGroup::ANONYMOUS,
        );
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns($groups);
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns($groups);
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns($groups);

        $this->project_creator->createGerritProject($this->server, $this->repository_without_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertNoPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsLabelCodeReviewOnceIfUserCanReadANdWrite()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::PROJECT_MEMBERS));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::PROJECT_MEMBERS));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertNoPattern('/label-Code-Review = -1..+1/', file_get_contents("$this->gerrit_tmpdir/project.config"));
        $this->assertPattern('/label-Code-Review = -2..+2/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasReadForRegistered()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasWriteForRegistered()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasExecuteForRegistered()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(ProjectUGroup::REGISTERED));

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function isSetsPermsOnRegisteredUsersIfRepoHasReadForAuthenticated()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::AUTHENTICATED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasWriteForAuthenticated()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::AUTHENTICATED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasExecuteForAuthenticated()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(ProjectUGroup::AUTHENTICATED));

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasReadForAnonymous()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(ProjectUGroup::ANONYMOUS));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasWriteForAnonymous()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(ProjectUGroup::ANONYMOUS));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array());

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    public function itSetsPermsOnRegisteredUsersIfRepoHasExecuteForAnonymous()
    {
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array());
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(ProjectUGroup::ANONYMOUS));

        $this->project_creator->createGerritProject($this->server, $this->repository_with_registered, $this->migrate_access_rights);
        $this->project_creator->finalizeGerritProjectCreation($this->server, $this->repository, $this->template_id);

        $this->assertPattern('/Registered Users/', file_get_contents("$this->gerrit_tmpdir/project.config"));
    }

    private function assertItClonesTheDistantRepo()
    {
        $groups_file = "$this->gerrit_tmpdir/groups";
        $config_file = "$this->gerrit_tmpdir/project.config";
        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }

    private function assertCommitterIsConfigured()
    {
        $this->assertEqual(trim(`cd $this->gerrit_tmpdir; git config --get user.name`), $this->gerrit_admin_instance);
        $this->assertEqual(trim(`cd $this->gerrit_tmpdir; git config --get user.email`), 'codendiadm@'. $this->tuleap_instance);
    }

    private function assertTheRemoteOriginIsConfigured()
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec('git remote -v', $output, $ret_val);
        chdir($cwd);

        $port          = $this->server->getSSHPort();
        $identity_file = $this->server->getIdentityFile();
        $host_login    = $this->server->getLogin() . '@' . $this->server->getHost();

        $this->assertEqual($output, array(
//            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (fetch)",
//            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (push)",
            "origin\t$this->gerrit_git_url (fetch)",
            "origin\t$this->gerrit_git_url (push)",
            ));
        $this->assertEqual($ret_val, 0);
    }

    private function assertEverythingIsPushedToTheServer()
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec('git push origin HEAD:refs/meta/config --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array(
            "To $this->gerrit_git_url",
            "=\tHEAD:refs/meta/config\t[up to date]",
            "Done"));
        $this->assertEqual($ret_val, 0);
    }

    private function assertEverythingIsCommitted()
    {
        $cwd = getcwd();
        chdir("$this->gerrit_tmpdir");
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }

    private function assertPermissionsFileHasEverything()
    {
        $config_file_contents = file_get_contents("$this->gerrit_tmpdir/project.config");
        $expected_contents    = file_get_contents("$this->fixtures/expected_access_rights.config"); // TODO: To be completed

        $this->assertEqual($config_file_contents, $expected_contents);
    }

    private function assertGroupsFileHasEverything()
    {
        $groups_file = "$this->gerrit_tmpdir/groups";
        $group_file_contents = file_get_contents($groups_file);

        $this->assertPattern("%$this->project_members_uuid\t$this->project_members_gerrit_name\n%", $group_file_contents);
        $this->assertPattern("%$this->another_ugroup_uuid\t$this->another_ugroup_gerrit_name\n%", $group_file_contents);
        $this->assertPattern("%$this->replication_uuid\t$this->replication\n%", $group_file_contents);
        $this->assertPattern("%global:Registered-Users\tRegistered Users\n%", $group_file_contents);
    }
}
