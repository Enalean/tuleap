<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';
require_once 'common/include/Config.class.php';
require_once dirname(__FILE__).'/../../../../ldap/include/LDAP_User.class.php';

abstract class Git_Driver_Gerrit_baseTest extends TuleapTestCase {

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
        $this->driver = new Git_Driver_Gerrit($this->ssh, $this->logger);
    }

}

class Git_Driver_Gerrit_createProjectTest extends Git_Driver_Gerrit_baseTest {

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
        $this->driver->createParentProject($this->gerrit_server, $this->project, 'firefox/project_admins');
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
        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_Gerrit::EXIT_CODE, '', $std_err));
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

class Git_Driver_Gerrit_createGroupTest extends Git_Driver_Gerrit_baseTest {

    public function setUp() {
        parent::setUp();
        $this->gerrit_driver = partial_mock('Git_Driver_Gerrit', array('DoesTheGroupExist'), array($this->ssh, $this->logger));
    }

    public function itCreatesGroupsIfItNotExistsOnGerrit() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(true);

        $create_group_command = "gerrit create-group firefox/project_members --owner firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->never();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_members', 'firefox/project_admins');
    }

    public function itInformsAboutGroupCreation() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $user_list    = array ();
        expect($this->logger)->info("Gerrit: Group firefox/project_members successfully created")->once();
        $this->gerrit_driver->createGroup($this->gerrit_server,  'firefox/project_members', $user_list);
    }

    public function itRaisesAGerritDriverExceptionOnGroupsCreation(){
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $std_err = 'fatal: group "somegroup" already exists';
        $command = "gerrit create-group firefox/project_members --owner firefox/project_admins";

        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_Gerrit::EXIT_CODE, '', $std_err));

        try {
            $this->gerrit_driver->createGroup($this->gerrit_server,  'firefox/project_members', 'firefox/project_admins');
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(), "Command: $command" . PHP_EOL . "Error: $std_err");
        }
    }

    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue() {
        stub($this->gerrit_driver)->DoesTheGroupExist()->returns(false);

        $create_group_command = "gerrit create-group firefox/project_admins";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->gerrit_driver->createGroup($this->gerrit_server, 'firefox/project_admins', 'firefox/project_admins');
    }
}

class Git_Driver_Gerrit_getGroupUUIDTest extends Git_Driver_Gerrit_baseTest {

    private $groupname = 'project/repo-contributors';
    private $expected_query = 'gerrit gsql --format json -c "SELECT\ *\ FROM\ account_groups\ WHERE\ name=\\\'project/repo-contributors\\\'"';

    public function itAsksGerritForTheGroupUUID() {
        $uuid         = 'lsalkj4jlk2jj3452lkj23kj421465';
        $query_result = '{"type":"row","columns":{"group_uuid":"'. $uuid .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($uuid, $this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullIfNotFound() {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupUUID($this->gerrit_server, $this->groupname));
    }
}

class Git_Driver_Gerrit_getGroupIdTest extends Git_Driver_Gerrit_baseTest {

    private $groupname = 'project/repo-contributors';
    private $expected_query = 'gerrit gsql --format json -c "SELECT\ *\ FROM\ account_groups\ WHERE\ name=\\\'project/repo-contributors\\\'"';

    public function itAsksGerritForTheGroupId() {
        $id         = '272';
        $query_result = '{"type":"row","columns":{"group_id":"'. $id .'"}}'.
                        PHP_EOL .
                        '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":1}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertEqual($id, $this->driver->getGroupId($this->gerrit_server, $this->groupname));
    }

    public function itReturnsNullIfNotFound() {
        $query_result = '{"type":"query-stats","rowCount":0,"runTimeMilliseconds":0}';
        stub($this->ssh)->execute($this->gerrit_server, $this->expected_query)->once()->returns($query_result);

        $this->assertNull($this->driver->getGroupId($this->gerrit_server, $this->groupname));
    }
}

class Git_Driver_Gerrit_addUserToGroupTest extends Git_Driver_Gerrit_baseTest {

    private $groupname;
    private $ldap_uid;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->group                       = 'contributors';
        $this->groupname                   = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;
        $this->ldap_uid                    = 'someuser';
        $this->user                        = new Git_Driver_Gerrit_User(stub('LDAP_User')->getUid()->returns($this->ldap_uid));

        $this->insert_member_query = 'gerrit gsql --format json -c "INSERT\ INTO\ account_group_members\ (account_id,\ group_id)\ SELECT\ A.account_id,\ G.group_id\ FROM\ account_external_ids\ A,\ account_groups\ G\ WHERE\ A.external_id=\\\'username:'. $this->ldap_uid .'\\\'\ AND\ G.name=\\\''. $this->groupname .'\\\'"';

        $this->set_account_query   = 'gerrit set-account '.$this->ldap_uid;
    }

    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI() {
        expect($this->ssh)->execute($this->gerrit_server, $this->set_account_query)->at(0);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheInsertCommand() {
        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $this->insert_member_query)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }
}

class Git_Driver_Gerrit_removeUserFromGroupTest extends Git_Driver_Gerrit_baseTest {

    private $groupname;
    private $ldap_uid;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->group          = 'contributors';
        $this->groupname      = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;
        $this->ldap_uid        = 'someuser';
        $this->user           = new Git_Driver_Gerrit_User(stub('LDAP_User')->getUid()->returns($this->ldap_uid));

    }

    public function itExecutesTheDeletionCommand() {
        $remove_member_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_members\ WHERE\ account_id=(SELECT\ account_id\ FROM\ account_external_ids\ WHERE\ external_id=\\\'username:'. $this->ldap_uid .'\\\')\ AND\ group_id=(SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\')"';

        expect($this->ssh)->execute($this->gerrit_server, $remove_member_query)->once();

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itRemovesAllMembers() {
        $remove_all_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_members\ WHERE\ group_id=(SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\')"';
        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $remove_all_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache accounts')->at(1);

        $this->driver->removeAllGroupMembers($this->gerrit_server, $this->groupname);
    }
}

class Git_Driver_Gerrit_GroupExistsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->ls_group_return = array(
            'Administrators',
            'Anonymous Users',
            'Non-Interactive Users',
            'Project Owners',
            'Registered Users',
            'project/project_members',
            'project/project_admins',
            'project/group_from_ldap',
        );

        $this->gerrit_driver = partial_mock('Git_Driver_Gerrit', array('listGroups'));
        stub($this->gerrit_driver)->listGroups()->returns($this->ls_group_return);

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
    }

    public function itCallsLsGroups() {
        expect($this->gerrit_driver)->listGroups($this->gerrit_server)->once();
        $this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'whatever');
    }

    public function itReturnsTrueIfGroupExists() {
        $this->assertTrue($this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'project/project_admins'));
    }

    public function itReturnsFalseIfGroupDoNotExists() {
        $this->assertFalse($this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'project/wiki_admins'));
    }
}

class Git_Driver_Gerrit_LsGroupsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_Gerrit($this->ssh, $this->logger);
    }

    public function itUsesGerritSSHCommandToListGroups() {
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit ls-groups')->once();
        $this->driver->listGroups($this->gerrit_server);
    }

    public function itReturnsAllPlatformGroups() {
        $ls_groups_expected_return = array(
            'Administrators',
            'Anonymous Users',
            'Non-Interactive Users',
            'Project Owners',
            'Registered Users',
            'project/project_members',
            'project/project_admins',
            'project/group_from_ldap',
        );

        $ssh_ls_groups = 'Administrators
Anonymous Users
Non-Interactive Users
Project Owners
Registered Users
project/project_members
project/project_admins
project/group_from_ldap';

        stub($this->ssh)->execute()->returns($ssh_ls_groups);

        $this->assertEqual(
            $ls_groups_expected_return,
            $this->driver->listGroups($this->gerrit_server)
        );
    }
}

class Git_Driver_Gerrit_ProjectExistsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->ls_project_return = array(
            'All-Projects',
            'project',
        );

        $this->gerrit_driver = partial_mock('Git_Driver_Gerrit', array('listParentProjects'));
        stub($this->gerrit_driver)->listParentProjects()->returns($this->ls_project_return);

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
    }

    public function itReturnsTrueIfParentProjectExists() {
        $this->assertTrue($this->gerrit_driver->doesTheParentProjectExist($this->gerrit_server, 'project'));
    }

    public function itReturnsFalseIfParentProjectDoNotExists() {
        $this->assertFalse($this->gerrit_driver->doesTheParentProjectExist($this->gerrit_server, 'project_not_existing'));
    }
}

class Git_Driver_Gerrit_LsParentProjectsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_Gerrit($this->ssh, $this->logger);
    }

    public function itUsesGerritSSHCommandToListParentProjects() {
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit ls-projects --type PERMISSIONS')->once();
        $this->driver->listParentProjects($this->gerrit_server);
    }

    public function itReturnsAllPlatformParentProjects() {
        $ls_projects_expected_return = array(
            'project',
            'project/project_members',
            'project/project_admins',
            'project/group_from_ldap',
        );

        $ssh_ls_projects = 'project
project/project_members
project/project_admins
project/group_from_ldap';

        stub($this->ssh)->execute()->returns($ssh_ls_projects);

        $this->assertEqual(
            $ls_projects_expected_return,
            $this->driver->listParentProjects($this->gerrit_server)
        );
    }
}

class Git_Driver_Gerrit_AddIncludedGroupTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_Gerrit($this->ssh, $this->logger);
    }

    public function itAddAnIncludedGroup() {
        $group_name    = 'gdb/developers';
        $included_group_name = 'gcc/coders';
        $insert_included_query = 'gerrit gsql --format json -c "INSERT\ INTO\ account_group_includes\ (group_id,\ include_id)\ SELECT\ G.group_id,\ I.group_id\ FROM\ account_groups\ G,\ account_groups\ I\ WHERE\ G.name=\\\''. $group_name .'\\\'\ AND\ I.name=\\\''. $included_group_name .'\\\'"';

        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $insert_included_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache groups_byinclude')->at(1);

        $this->driver->addIncludedGroup($this->gerrit_server, $group_name, $included_group_name);
    }
}

class Git_Driver_Gerrit_RemoveIncludedGroupTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = partial_mock('Git_Driver_Gerrit', array('getGroupId'), array($this->ssh, $this->logger));
    }

    public function itAddAnIncludedGroup() {
        $id = 272;
        $group_name    = 'gdb/developers';

        stub($this->driver)->getGroupId($this->gerrit_server, $group_name)->returns($id);

        $delete_included_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_includes\ I\ WHERE\ I.group_id='.$id.'"';

        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $delete_included_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache groups_byinclude')->at(1);

        $this->driver->removeAllIncludedGroups($this->gerrit_server, $group_name);
    }
}
?>
