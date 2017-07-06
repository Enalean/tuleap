<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Svn;

require_once __DIR__ .'/../bootstrap.php';

use Backend;
use BackendSVN;
use EventManager;
use ForgeConfig;
use Project;
use ProjectManager;
use SimpleXMLElement;
use SystemEventManager;
use Tuleap\HudsonGit\Logger;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Repository\RepositoryCreator;
use Tuleap\Svn\Repository\RuleName;
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
     * @var Backend
     */
    private $backend;

    public function setUp()
    {
        global $Language;
        parent::setUp();

        ForgeConfig::store();
        $this->arpath = parent::getTmpDir();
        ForgeConfig::set('sys_data_dir', $this->arpath);
        chmod($this->arpath, 0755); // codendiadm should be able to read the base dir to load data within
        ForgeConfig::set('sys_http_user', 'codendiadm');
        ProjectManager::clearInstance();

        $this->backend            = mock('Backend');
        $this->logger             = mock('Logger');
        $this->pmdao              = safe_mock('ProjectDao');
        $this->evdao              = safe_mock('SystemEventDao');
        $this->evfdao             = safe_mock('SystemEventsFollowersDao');
        $this->pm                 = ProjectManager::testInstance($this->pmdao);
        $this->repodao            = safe_mock('Tuleap\Svn\Dao');
        $this->sysevmgr           = SystemEventManager::testInstance($this->evdao, $this->evfdao);
        $this->ugdao              = safe_mock('UGroupDao');
        $this->ugudao             = safe_mock('UGroupUserDao');
        $this->accessfiledao      = safe_mock('Tuleap\Svn\AccessControl\AccessFileHistoryDao');
        $this->accessfilefac      = new AccessFileHistoryFactory($this->accessfiledao);
        $this->accessfilemgr      = new AccessFileHistoryCreator($this->accessfiledao, $this->accessfilefac);
        $this->notifdao           = safe_mock('Tuleap\Svn\Admin\MailNotificationDao');
        $users_to_notify_dao      = safe_mock('Tuleap\Svn\Notifications\UsersToNotifyDao');
        $ugroups_to_notify_dao    = safe_mock('Tuleap\Svn\Notifications\UgroupsToNotifyDao');
        $this->notifdao           = safe_mock('Tuleap\Svn\Admin\MailNotificationDao');
        $this->notifmgr           = new MailNotificationManager(
            $this->notifdao,
            $users_to_notify_dao,
            $ugroups_to_notify_dao
        );
        $this->repository_creator = new RepositoryCreator(
            $this->repodao,
            $this->sysevmgr,
            mock('ProjectHistoryDao')
        );

        Backend::clearInstances();
        Backend::instance(Backend::SVN, 'Tuleap\Svn\TestBackendSVN', array($this));
        $Language = mock('BaseLanguage');
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
        stub($this->repodao)->doesRepositoryAlreadyExist()
            ->once("Check the repository name is not taken")
            ->returns(false);
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
    }

    private function callImport(XMLImporter $importer, Project $project) {
        $importer->import(
            new ImportConfig(),
            $this->logger,
            $project,
            $this->accessfilemgr,
            $this->notifmgr,
            new RuleName($project, $this->repodao));
    }

    public function itShouldImportOneRevision() {
        copy(__DIR__ . '/../_fixtures/svn_2revs.dump', "{$this->arpath}/svn.dump");
        $xml = new SimpleXMLElement('<project><svn><repository name="svn" dump-file="svn.dump"/></svn></project>');
        $project = $this->pm->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));

        $this->stubRepoCreation(123, 85, 1585);

        $svn = new XMLImporter($this->backend, $xml, $this->arpath, $this->repository_creator);
        $this->callImport($svn, $project);

        $this->assertFileIsOwnedBy('codendiadm', $this->arpath.'/svn_plugin/123/svn');

        $this->assertRevision(1, 123, "svn");
    }

    private function assertFileIsOwnedBy($user, $file) {
        $stat = stat($file);
        $user = posix_getpwnam($user);
        $this->assertIdentical($stat['uid'], $user['uid']);
    }

    public function itShouldDoNothingIfNoSvnNode() {
        $project = $this->pm->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));
        $xml = new SimpleXMLElement('<project></project>');

        stub($this->repodao)->doesRepositoryAlreadyExist()->never();
        stub($this->repodao)->create()->never();
        stub($this->evdao)->store()->never();

        $svn = new XMLImporter($this->backend, $xml, $this->arpath, $this->repository_creator);
        $this->callImport($svn, $project);
    }

    public function itShouldFailToImportIfTheSVNFileIsNotPresent() {
        $project = $this->pm->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));
        $xml = new SimpleXMLElement('<project><svn><repository name="svn" dump-file="non-existant-svn.dump"/></svn></project>');

        $this->expectException('Tuleap\Svn\XMLImporterException');

        $this->stubRepoCreation(123, 85, 1585);

        $svn = new XMLImporter($this->backend, $xml, $this->arpath, $this->repository_creator);
        $this->callImport($svn, $project);
    }

    public function itShouldImportNotifications(){
        $project = $this->pm->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));
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
        stub($this->notifdao)->create()->count(2)->returns(true);

        $svn = new XMLImporter($this->backend, new SimpleXMLElement($xml), $this->arpath, $this->repository_creator);
        $this->callImport($svn, $project);
    }

    public function itShouldImportSvnAccessFile() {
        $project = $this->pm->getProjectFromDbRow(array(
            'group_id' => 123,
            'unix_group_name' => 'test_project',
            'access' => 'private',
            'svn_tracker' => null,
            'svn_can_change_log' => null));
        $access_file = "[groups]\nmembers = usernameTOTO123\n\n\n[/]\n* = r\n@members = rw\n";
        $xml = <<<XML
            <project>
                <svn>
                    <repository name="svn">
                        <access-file>$access_file</access-file>
                    </repository>
                </svn>
            </project>
XML;

        $this->stubRepoCreation(123, 85, 1585);
        stub($this->accessfiledao)->searchLastVersion()->once()->returns(null);
        stub($this->accessfiledao)->create()->once()->returns(true);

        $svn = new XMLImporter($this->backend, new SimpleXMLElement($xml), $this->arpath, $this->repository_creator);
        $this->callImport($svn, $project);

        $svnroot = $this->getSVNDir(123, "svn");
        $accessfile = file_get_contents("$svnroot/.SVNAccessFile");
        $found = strstr($accessfile, "TOTO123") !== false;
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
