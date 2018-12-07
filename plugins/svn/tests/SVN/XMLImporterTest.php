<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN;

require_once __DIR__ .'/../bootstrap.php';

use Backend;
use BackendSVN;
use EventManager;
use ForgeConfig;
use Mockery;
use Project;
use ProjectManager;
use SimpleXMLElement;
use SystemEventManager;
use Tuleap\HudsonGit\Logger;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;
use TuleapTestCase;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;

class TestBackendSVN extends BackendSVN {
    private $tc; ///< @var XMLImporterTest

    public function setUp($test_case) {
        $this->tc = $test_case;
    }

    protected function getProjectManager() {
        return $this->tc->pm;
    }

    protected function getUGroupDao() {
        return $this->tc->ugdao;
    }

    protected function getUGroupManager() {
        return new UGroupManager($this->tc->ugdao, null, $this->tc->ugudao);
    }

    public function chgrp($path, $uid) {
        return true;
    }
}

class XMLImporterTest extends TuleapTestCase
{
    /**
     * @var RepositoryCopier
     */
    private $repository_copier;
    /**
     * @var NotificationsEmailsBuilder
     */
    private $notification_emails_builder;
    /**
     * @var AccessFileHistory
     */
    private $access_file;
    /**
     * @var Backend
     */
    private $backend_system;
    /**
     * @var Backend
     */
    private $backend_svn;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var RuleName
     */
    private $rule_name;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var RepositoryCreator
     */
    private $repository_creator;
    private $arpath;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \ProjectDao
     */
    private $pmdao;

    /**
     * @var \SystemEventDao
     */
    private $evdao;

    /**
     * @var \SystemEventsFollowersDao
     */
    private $evfdao;

    /**
     * @var \ProjectManager
     */
    public $pm;

    /**
     * @var Dao
     */
    private $repodao;

    /**
     * @var \SystemEventManager
     */
    private $sysevmgr;

    /**
     * @var UGroupDao
     */
    public  $ugdao;

    /**
     * @var UGroupUserDao
     */
    public  $ugudao;

    /**
     * @var AccessFileHistoryDao
     */
    private $accessfiledao;

    /**
     * @var AccessFileHistoryFactory
     */
    private $accessfilefac;

    /**
     * @var AccessFileHistoryCreator
     */
    private $accessfilemgr;

    /**
     * @var MailNotificationDao
     */
    private $notifdao;

    /**
     * @var MailNotificationManager
     */
    private $notifmgr;

    /**
     * @var \UserManager
     */
    private $user_manager;

    public function setUp()
    {
        global $Language;
        parent::setUp();
        $this->setUpGlobalsMockery();

        ForgeConfig::store();
        $this->arpath = parent::getTmpDir();
        ForgeConfig::set('sys_data_dir', $this->arpath);
        chmod($this->arpath, 0755); // codendiadm should be able to read the base dir to load data within
        ForgeConfig::set('sys_http_user', 'codendiadm');
        ProjectManager::clearInstance();

        $this->user_manager       = \Mockery::spy(\UserManager::class);
        $this->ugroup_manager     = \Mockery::spy(\UgroupManager::class);
        $this->logger             = \Mockery::spy(\Logger::class);
        $this->pmdao              = safe_mock('ProjectDao');
        $this->evdao              = safe_mock('SystemEventDao');
        $this->evfdao             = safe_mock('SystemEventsFollowersDao');
        $this->pm                 = ProjectManager::testInstance($this->pmdao);
        $this->repodao            = safe_mock('Tuleap\SVN\Dao');
        $this->sysevmgr           = SystemEventManager::testInstance($this->evdao, $this->evfdao);
        $this->ugdao              = safe_mock('UGroupDao');
        $this->ugudao             = safe_mock('UGroupUserDao');
        $this->accessfiledao      = safe_mock('Tuleap\SVN\AccessControl\AccessFileHistoryDao');
        $this->accessfilefac      = new AccessFileHistoryFactory($this->accessfiledao);
        $project_history_dao      = \Mockery::spy(\ProjectHistoryDao::class);
        $this->accessfilemgr      = new AccessFileHistoryCreator(
            $this->accessfiledao,
            $this->accessfilefac,
            $project_history_dao,
            \Mockery::spy(\Tuleap\SVN\Repository\ProjectHistoryFormatter::class)
        );

        $users_to_notify_dao      = safe_mock('Tuleap\SVN\Notifications\UsersToNotifyDao');
        $ugroups_to_notify_dao    = safe_mock('Tuleap\SVN\Notifications\UgroupsToNotifyDao');
        $this->notifdao           = safe_mock('Tuleap\SVN\Admin\MailNotificationDao');
        $this->notifmgr           = new MailNotificationManager(
            $this->notifdao,
            $users_to_notify_dao,
            $ugroups_to_notify_dao,
            $project_history_dao,
            new NotificationsEmailsBuilder(),
            $this->ugroup_manager
        );

        $permissions_manager      = \Mockery::spy(\Tuleap\SVN\SvnPermissionManager::class);
        $this->repository_creator = new RepositoryCreator(
            $this->repodao,
            $this->sysevmgr,
            $project_history_dao,
            $permissions_manager,
            \Mockery::spy(\Tuleap\SVN\Repository\HookConfigUpdator::class),
            new ProjectHistoryFormatter(),
            \Mockery::spy(\Tuleap\SVN\Admin\ImmutableTagCreator::class),
            \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class),
            \Mockery::spy(\Tuleap\SVN\Admin\MailNotificationManager::class)
        );

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user = aUser()->build();
        stub($permissions_manager)->isAdmin()->returns(true);

        Backend::clearInstances();

        $Language = \Mockery::spy(\BaseLanguage::class);

        $this->project = $this->pm->getProjectFromDbRow(
            array(
                'group_id'           => 123,
                'unix_group_name'    => 'test_project',
                'access'             => 'private',
                'svn_tracker'        => null,
                'svn_can_change_log' => null
            )
        );

        $this->rule_name = new RuleName($this->project, $this->repodao);

        $this->access_file_history_creator = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_manager          = \Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);
        $this->backend_svn                 = Backend::instance(Backend::SVN, TestBackendSVN::class, array($this));
        $this->backend_system              = \Mockery::spy(\BackendSystem::class);
        $this->access_file                 = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistory::class);
        $this->notification_emails_builder = new NotificationsEmailsBuilder();
        $this->repository_copier           = \Mockery::spy(\Tuleap\SVN\Migration\RepositoryCopier::class);
    }

    public function tearDown() {
        global $Language;
        unset($Language);
        ForgeConfig::restore();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        EventManager::clearInstance();
        Backend::clearInstances();

        parent::tearDown();
    }

    private function stubRepoCreation($project_id, $repo_id, $event_id){
        stub($this->repodao)->create()
            ->once("Create the repository")
            ->returns($repo_id);
        stub($this->evdao)->store()
            ->once("Create a system event")
            ->returns($event_id);
        stub($this->evfdao)->searchByType()
            ->once("To notify event listeners")
            ->returns(array());
        // To generate .SVNAccessFile
        stub($this->pmdao)->getProjectMembers($project_id)
            ->returnsEmptyDar();
        stub($this->ugdao)->searchByGroupId($project_id)
            ->returnsEmptyDar();
        stub($this->ugudao)->searchUserByDynamicUGroupId(3, $project_id)
            ->returnsEmptyDar();
        $this->repository_manager->shouldReceive('getRepositoryById')->andReturn(Mockery::spy(Repository::class));
    }

    private function callImport(XMLImporter $importer, Project $project)
    {
        $importer->import(
            new ImportConfig(),
            $this->logger,
            $project,
            $this->accessfilemgr,
            $this->notifmgr,
            $this->rule_name,
            $this->user
        );
    }

    public function itShouldImportOneRevision()
    {
        copy(__DIR__ . '/../_fixtures/svn_2revs.dump', "{$this->arpath}/svn.dump");
        $xml = new SimpleXMLElement('<project><svn><repository name="svn01" dump-file="svn.dump"/></svn></project>');

        $this->stubRepoCreation(123, 85, 1585);
        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(false);
        stub($this->accessfiledao)->searchCurrentVersion()->returns(false);

        stub($this->user_manager)->getUserById()->returns(\Mockery::spy(\PFUser::class));

        $svn = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn, $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);

        $this->assertFileIsOwnedBy('codendiadm', $this->arpath.'/svn_plugin/123/svn01');

        $this->assertRevision(1, 123, "svn01");
    }

    private function assertFileIsOwnedBy($user, $file) {
        $stat = stat($file);
        $user = posix_getpwnam($user);
        $this->assertIdentical($stat['uid'], $user['uid']);
    }

    public function itShouldDoNothingIfNoSvnNode()
    {
        $xml = new SimpleXMLElement('<project></project>');

        stub($this->repodao)->doesRepositoryAlreadyExist()->never();
        stub($this->repodao)->create()->never();
        stub($this->evdao)->store()->never();

        $svn = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn, $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier

        );
        $this->callImport($svn, $this->project);
    }

    public function itShouldFailsWhenRepositoryNameIsInvalid()
    {
        $xml = new SimpleXMLElement('<project><svn><repository name="1" /></svn></project>');

        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(false);
        $this->expectException('Tuleap\SVN\XMLImporterException');

        $svn = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn, $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);
    }

    public function itShouldFailsWhenRepositoryNameIsAlreadyExist()
    {
        $xml = new SimpleXMLElement('<project><svn><repository name="svn01" /></svn></project>');

        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(true);
        $this->expectException('Tuleap\SVN\XMLImporterException');

        $svn = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn, $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);
    }

    public function itShouldFailToImportIfTheSVNFileIsNotPresent()
    {
        $xml = new SimpleXMLElement(
            '<project><svn><repository name="svn01" dump-file="non-existant-svn.dump"/></svn></project>'
        );

        stub($this->user_manager)->getUserById()->returns(\Mockery::spy(\PFUser::class));
        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(false);
        $this->expectException('Tuleap\SVN\XMLImporterException');

        $this->stubRepoCreation(123, 85, 1585);

        $svn = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn, $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);
    }

    public function itShouldImportNotifications()
    {
        $xml = <<<XML
            <project>
                <svn>
                    <repository name="svn">
                        <notification path="/trunk" emails="test1@domain1, test2@domain2"/>
                        <notification path="/tags" emails="tags@domain3"/>
                    </repository>
                </svn>
            </project>
XML;

        $this->stubRepoCreation(123, 85, 1585);
        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(false);
        stub($this->notifdao)->create()->count(2)->returns(true);
        stub($this->user_manager)->getUserById()->returns(\Mockery::spy(\PFUser::class));
        stub($this->accessfiledao)->searchCurrentVersion()->returns(false);

        $svn = new XMLImporter(
            new SimpleXMLElement($xml),
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);
    }

    public function itShouldImportSvnAccessFile()
    {
        $access_file = "[groups]\nmembers = usernameTOTO123\n\n\n[/]\n* = r\n@members = rw\n";
        $xml         = <<<XML
            <project>
                <svn>
                    <repository name="svn01">
                        <access-file>$access_file</access-file>
                    </repository>
                </svn>
            </project>
XML;

        stub($this->repodao)->doesRepositoryAlreadyExist()->returns(false);
        $this->stubRepoCreation(123, 85, 1585);
        stub($this->accessfiledao)->searchLastVersion()->once()->returns(null);
        stub($this->accessfiledao)->create()->once()->returns(true);
        stub($this->user_manager)->getUserById()->returns(\Mockery::spy(\PFUser::class));
        stub($this->accessfiledao)->searchCurrentVersion()->returns(false);

        $svn = new XMLImporter(
            new SimpleXMLElement($xml),
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier
        );
        $this->callImport($svn, $this->project);

        $svnroot    = $this->getSVNDir(123, "svn01");
        $accessfile = file_get_contents("$svnroot/.SVNAccessFile");
        $found      = strstr($accessfile, "TOTO123") !== false;
        $this->assertTrue($found, "$svnroot/.SVNAccessFile:\n$accessfile");
    }

    private function assertRevision($expected, $project_id, $repo_name) {
        $svn_dir = $this->getSVNDir($project_id, $repo_name);
        $svn_arg = escapeshellarg("file://$svn_dir");
        $cmd_line = "(svn info $svn_arg | grep Revision) 2>&1";
        $last_changed_revision = shell_exec($cmd_line);
        $this->assertEqual("Revision: $expected\n", $last_changed_revision);
    }

    private function getSVNDir($project_id, $repo_name) {
       return ForgeConfig::get('sys_data_dir')."/svn_plugin/$project_id/$repo_name";
    }
}
