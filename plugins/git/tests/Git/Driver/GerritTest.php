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

require_once dirname(__FILE__).'/../../../include/constants.php';
require_once dirname(__FILE__).'/../../builders/aGitRepository.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';
abstract class Git_Driver_Gerrit_baseTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();

        $this->project_name    = 'firefox';
        $this->namespace       = 'jean-claude';
        $this->repository_name = 'dusse';

        $project = stub('Project')->getUnixName()->returns($this->project_name);

        $this->repository = aGitRepository()
            ->withProject($project)
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


    public function itExecutesTheCreateCommandOnTheGerritServer() {
        expect($this->ssh)->execute($this->gerrit_server, "gerrit create-project firefox/jean-claude/dusse")->once();
        $this->driver->createProject($this->gerrit_server, $this->repository);
    }

    public function itReturnsTheNameOfTheCreatedProject() {
        $project_name = $this->driver->createProject($this->gerrit_server, $this->repository);
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
        $command = "gerrit create-project firefox/jean-claude/dusse";
        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_Gerrit::EXIT_CODE, '', $std_err));
        try {
            $this->driver->createProject($this->gerrit_server, $this->repository);
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(),"Command: $command".PHP_EOL."Error: $std_err");
        }
    }

    public function itDoesntTransformExceptionsThatArentRelatedToGerrit() {
        $std_err = 'some gerrit exception';
        $this->expectException('Git_Driver_Gerrit_RemoteSSHCommandFailure');
        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(255,'',$std_err));
        $this->driver->createProject($this->gerrit_server, $this->repository);
    }

    public function itInformsAboutProjectInitialization() {
        $remote_project = "firefox/jean-claude/dusse";
        expect($this->logger)->info("Gerrit: Project $remote_project successfully initialized")->once();
        $this->driver->createProject($this->gerrit_server, $this->repository);

    }
}

class Git_Driver_Gerrit_createGroupTest extends Git_Driver_Gerrit_baseTest {

    public function itCreatesGroups() {
        $project_name = "firefox/jean-claude/dusse";
        $group_name = $project_name."-contributors";
        $create_group_command = "gerrit create-group $group_name";
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->driver->createGroup($this->gerrit_server, $this->repository, 'contributors', array());
    }

    public function itCreatesGroupsWithMembers() {
        $project_name = "firefox/jean-claude/dusse";
        $group_name = $project_name."-contributors";
        $create_group_command = "gerrit create-group $group_name --member ''\''johan'\''' --member ''\''goyotm'\'''";
        $user_list = array('johan', 'goyotm');
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->driver->createGroup($this->gerrit_server, $this->repository, 'contributors', $user_list);
    }

    public function itInformsAboutGroupCreation() {
        $group_name   = 'contributors';
        $user_list    = array ();
        $gerrit_group = "firefox/jean-claude/dusse-$group_name";
        expect($this->logger)->info("Gerrit: Group $gerrit_group successfully created")->once();
        $this->driver->createGroup($this->gerrit_server, $this->repository, $group_name, $user_list);
    }

    public function itRaisesAGerritDriverExceptionOnGroupsCreation(){
        $std_err = 'fatal: group "somegroup" already exists';
        $command = "gerrit create-group firefox/jean-claude/dusse-contributors --member ''\''johan'\'''";
        $user_list = array('johan');

        stub($this->ssh)->execute()->throws(new Git_Driver_Gerrit_RemoteSSHCommandFailure(Git_Driver_Gerrit::EXIT_CODE, '', $std_err));

        try {
            $this->driver->createGroup($this->gerrit_server, $this->repository, 'contributors', $user_list);
            $this->fail('An exception was expected');
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->assertEqual($e->getMessage(), "Command: $command" . PHP_EOL . "Error: $std_err");
        }
    }

    public function itEscapesTwiceUsernameInCommandLine() {
        $project_name         = "firefox/jean-claude/dusse";
        $group_name           = $project_name."-contributors";
        $create_group_command = "gerrit create-group $group_name --member ''\''Johan Martinsson'\'''";
        $user_list            = array('Johan Martinsson',);
        expect($this->ssh)->execute($this->gerrit_server, $create_group_command)->once();
        $this->driver->createGroup($this->gerrit_server, $this->repository, 'contributors', $user_list);
    }

}

class Git_Driver_Gerrit_getGroupIdTest extends Git_Driver_Gerrit_baseTest {

    private $groupname = 'project/repo-contributors';
    private $expected_query = 'gerrit gsql --format json -c "SELECT\ group_uuid\ FROM\ account_groups\ WHERE\ name=\\\'project/repo-contributors\\\'"';

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

class Git_Driver_Gerrit_addUserToGroupTest extends Git_Driver_Gerrit_baseTest {

    private $groupname;
    private $ldap_id;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->groupname      = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name;
        $this->ldap_id        = 'someuser';
        $this->user           = aUser()->withLdapId($this->ldap_id)->build();
        $this->account_id     = 1000003;
        $this->group_id       = 52;

        $this->get_account_query   = 'gerrit gsql --format json -c "SELECT\ account_id\ FROM\ accounts\ WHERE\ full_name=\\\''. $this->ldap_id .'\\\'"';
        $this->get_group_query     = 'gerrit gsql --format json -c "SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\'"';
        $this->insert_member_query = 'gerrit gsql --format json -c "INSERT\ INTO\ account_group_members\ (account_id, group_id)\ VALUES('. $this->account_id .',\ '. $this->group_id .')"';

        $this->get_account_result = '{"type":"row","columns":{"account_id":"'. $this->account_id .'"}}'.
                                    PHP_EOL .
                                    '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":2}';

        $this->get_group_result   = '{"type":"row","columns":{"group_id":"'. $this->group_id .'"}}'.
                                    PHP_EOL .
                                    '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":2}';


        stub($this->ssh)->execute($this->gerrit_server, $this->get_account_query)->returns($this->get_account_result);
        stub($this->ssh)->execute($this->gerrit_server, $this->get_group_query)->returns($this->get_group_result);
    }

    public function itExecutesTheInsertCommand() {
        expect($this->ssh)->execute($this->gerrit_server, $this->insert_member_query)->at(2);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->repository);
    }

    public function itAsksGerritForAccountId() {
        expect($this->ssh)->execute($this->gerrit_server, $this->get_account_query)->at(0);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->repository);
    }

    public function itAsksGerritForGroupId() {
        expect($this->ssh)->execute($this->gerrit_server, $this->get_group_query)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->repository);
    }
}

class Git_Driver_Gerrit_removeUserFromGroupTest extends Git_Driver_Gerrit_baseTest {

    private $groupname;
    private $ldap_id;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->groupname      = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name;
        $this->ldap_id        = 'someuser';
        $this->user           = aUser()->withLdapId($this->ldap_id)->build();
        $this->account_id     = 1000003;
        $this->group_id       = 52;

        $this->get_account_query   = 'gerrit gsql --format json -c "SELECT\ account_id\ FROM\ accounts\ WHERE\ full_name=\\\''. $this->ldap_id .'\\\'"';
        $this->get_group_query     = 'gerrit gsql --format json -c "SELECT\ group_id\ FROM\ account_groups\ WHERE\ name=\\\''. $this->groupname .'\\\'"';
        $this->remove_member_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_members\ WHERE\ account_id='. $this->account_id .'\ AND\ group_id='. $this->group_id .'"';

        $this->get_account_result = '{"type":"row","columns":{"account_id":"'. $this->account_id .'"}}'.
                                    PHP_EOL .
                                    '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":2}';

        $this->get_group_result   = '{"type":"row","columns":{"group_id":"'. $this->group_id .'"}}'.
                                    PHP_EOL .
                                    '{"type":"query-stats","rowCount":1,"runTimeMilliseconds":2}';

        stub($this->ssh)->execute($this->gerrit_server, $this->get_account_query)->returns($this->get_account_result);
        stub($this->ssh)->execute($this->gerrit_server, $this->get_group_query)->returns($this->get_group_result);
    }

    public function itExecutesTheDeletionCommand() {
        expect($this->ssh)->execute($this->gerrit_server, $this->remove_member_query)->at(2);

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->repository);
    }

    public function itAsksGerritForAccountId() {
        expect($this->ssh)->execute($this->gerrit_server, $this->get_account_query)->at(0);

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->repository);
    }

    public function itAsksGerritForGroupId() {
        expect($this->ssh)->execute($this->gerrit_server, $this->get_group_query)->at(1);

        $this->driver->removeUserFromGroup($this->gerrit_server, $this->user, $this->repository);
    }
}
?>
