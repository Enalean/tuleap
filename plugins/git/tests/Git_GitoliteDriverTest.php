<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__).'/../include/constants.php');
require_once dirname(__FILE__).'/../include/Git.class.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';

Mock::generate('Project');
Mock::generate('User');
Mock::generate('GitDao');
Mock::generate('PermissionsManager');
Mock::generate('DataAccessResult');
Mock::generate('Git_PostReceiveMailManager');

class GitoliteTestCase extends TuleapTestCase {
    
    /** @var Git_GitoliteDriver */
    protected $driver;
    
    public function setUp() {
        parent::setUp();
        $this->cwd           = getcwd();
        $this->_fixDir       = dirname(__FILE__).'/_fixtures';
        $tmpDir              = '/tmp';
        $this->_glAdmDirRef  = $tmpDir.'/gitolite-admin-ref';
        $this->_glAdmDir     = $tmpDir.'/gitolite-admin';
        $this->repoDir       = $tmpDir.'/repositories';
        
        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        system('tar -xf '. $this->_fixDir.'/gitolite-admin-ref' .'.tar --directory '.$tmpDir);
        symlink($this->_glAdmDirRef, $this->_glAdmDir);

        mkdir($this->repoDir);

        $this->httpsHost = $GLOBALS['sys_https_host'];

        $GLOBALS['sys_https_host'] = 'localhost';
        PermissionsManager::setInstance(new MockPermissionsManager());
        $this->permissions_manager = PermissionsManager::instance();
        $this->gitExec = partial_mock('Git_Exec', array('push'), array($this->_glAdmDir));
        stub($this->gitExec)->push()->returns(true);
        $this->driver = new Git_GitoliteDriver($this->_glAdmDir, $this->gitExec);
    }
    
    public function tearDown() {
        parent::tearDown();
        chdir($this->cwd);
    
        system('rm -rf '. $this->_glAdmDirRef);
        system('rm -rf '. $this->_glAdmDir .'/repositories/*');
        system('rm -rf '. $this->repoDir);
        unlink($this->_glAdmDir);
        $GLOBALS['sys_https_host'] = $this->httpsHost;
        PermissionsManager::clearInstance();
    }
    
    public function assertEmptyGitStatus() {
        $cwd = getcwd();
        chdir($this->_glAdmDir);
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }
    
    protected function assertNameSpaceFileHasBeenInitialized($repoPath, $namespace, $group) {
        $namespaceInfoFile = $repoPath.'/tuleap_namespace';
        $this->assertTrue(file_exists($namespaceInfoFile), 'the file (' . $namespaceInfoFile . ') does not exists');
        $this->assertEqual(file_get_contents($namespaceInfoFile), $namespace);
        $this->assertEqual($group, $this->_getFileGroupName($namespaceInfoFile));

    }
    
    protected function assertWritableByGroup($new_root_dir, $group) {
        $this->assertEqual($group, $this->_getFileGroupName($new_root_dir));
        $this->assertEqual($group, $this->_getFileGroupName($new_root_dir .'/hooks/gitolite_hook.sh'));

        clearstatcache();
        $rootStats = stat($new_root_dir);
        $this->assertPattern('/.*770$/', decoct($rootStats[2]));
    }
    
    protected function _getFileGroupName($filePath) {
        clearstatcache();
        $rootStats = stat($filePath);
        $groupInfo = posix_getgrgid($rootStats[5]);
        return $groupInfo['name'];
    }
    
    public function assertRepoIsClonedWithHooks($new_root_dir) {
        $this->assertTrue(is_dir($new_root_dir), "the new git repo dir ($new_root_dir) wasn't found.");
        $new_repo_HEAD = $new_root_dir . '/HEAD';
        $this->assertTrue(file_exists($new_repo_HEAD), 'the file (' . $new_repo_HEAD . ') does not exists');
        $this->assertTrue(file_exists($new_root_dir . '/hooks/gitolite_hook.sh'), 'the hook file wasn\'t copied to the fork');
    }

    public function arrayToDar() {
        $argList = func_get_args();
        $dar = new MockDataAccessResult();
        $rowCount = 0;
        foreach ($argList as $row) {
            $dar->setReturnValueAt($rowCount, 'valid', true);
            $dar->setReturnValueAt($rowCount, 'current', $row);
            $rowCount++;
        }
        $dar->setReturnValueAt($rowCount, 'valid', false);
        $dar->setReturnValue('rowCount', $rowCount);
        $dar->setReturnValue('isError', false);
        return $dar;
    }
    
    protected function _GivenARepositoryWithNameAndNamespace($name, $namespace) {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }
}

class Git_GitoliteDriver_UserKeysTest extends GitoliteTestCase {

    public function setUp() {
        parent::setUp();
        $this->key1 = 'ssh-rsa AAAAYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $this->key2 = 'ssh-rsa AAAAXYiTICSgWURDPDGW/HeNUYZIRcznQ== marcel@shanon.net';
    }

    public function testAddUserKey() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1))->build();

        $this->driver->dumpSSHKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testAddUserWithSeveralKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();

        $this->driver->dumpSSHKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $this->key1);
        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@1.pub'), $this->key2);

        $this->assertEmptyGitStatus();
    }

    public function testRemoveUserKey() {
        // User has 2 keys
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->driver->dumpSSHKeys($user);

        // internal push reset the pwd
        $this->driver->setAdminPath($this->_glAdmDir);
        
        // Now back with only one
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1))->build();
        $this->driver->dumpSSHKeys($user);

        // Ensure second key was deleted
        $this->assertFalse(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'), "Second key should be deleted");

        $this->assertEmptyGitStatus();
    }

    public function itDeletesAllTheKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->driver->dumpSSHKeys($user);

        // internal push reset the pwd
        $this->driver->setAdminPath($this->_glAdmDir);

        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array())->build();
        $this->driver->dumpSSHKeys($user);
        $this->assertCount(glob($this->_glAdmDir.'/keydir/*.pub'), 0);

        $this->assertEmptyGitStatus();
    }

    public function itDoesntGenerateAnyErrorsWhenThereAreNoChangesOnKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->driver->dumpSSHKeys($user);

        // After push directory change, so we need to re-changedir
        $this->driver->setAdminPath($this->_glAdmDir);
        $this->driver->dumpSSHKeys($user);
    }
}

class Git_GitoliteDriverTest extends GitoliteTestCase {

    public function testGitoliteConfUpdate() {
        // Test base: one gitolite conf + 1 project file
        file_put_contents($this->_glAdmDir.'/conf/gitolite.conf', '@test = coin'.PHP_EOL);
        touch($this->_glAdmDir.'/conf/projects/project1.conf');
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $this->driver->updateMainConfIncludes($prj);

        $gitoliteConf = $this->getGitoliteConf();
        // Original content still here
        $this->assertWantedPattern('#^@test = coin$#m', $gitoliteConf);
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    protected function getGitoliteConf() {
        return file_get_contents($this->_glAdmDir.'/conf/gitolite.conf');
    }
    
    public function testGetMailHookConfig() {        
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 101);

        // ShowRev
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev.txt'),
            $this->driver->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mail
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setNotifiedMails(array('john.doe@enalean.com', 'mme.michue@enalean.com'));
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail.txt'),
            $this->driver->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setNotifiedMails(array('john.doe@enalean.com', 'mme.michue@enalean.com'));
        $repo->setMailPrefix('[KOIN] ');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail-prefix.txt'),
            $this->driver->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mailprefix
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setNotifiedMails(array('john.doe@enalean.com', 'mme.michue@enalean.com'));
        $repo->setMailPrefix('["\_o<"] \t');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail-prefix-quote.txt'),
            $this->driver->fetchMailHookConfig($prj, $repo)
        );
    }

    public function ________________itAddsTheDescriptionToTheConfFile() {
        $driver = partial_mock('Git_GitoliteDriver', array('getDao', 'getPostReceiveMailManager'), array($this->_glAdmDir));
        //$driver->setAdminPath($this->_glAdmDir);
        $repo_description = 'Vive tuleap';
        $repo_name        = 'test_default';
        $project_name     = 'project1';

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', $project_name);

        // List all repo
        $dao = stub('GitDao')->getAllGitoliteRespositories()->returnsDar(array('repository_id' => 4, 
                    'repository_name' => $repo_name, 
                    'repository_namespace' => '', 
                    'repository_events_mailing_prefix' => "[SCM]", 
                    'repository_description' => $repo_description));
        $driver->setReturnValue('getDao', $dao);
        
        $permissions_manager = $this->permissions_manager;
        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('2'),   array(4, 'PLUGIN_GIT_READ'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('3'),   array(4, 'PLUGIN_GIT_WRITE'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(),      array(4, 'PLUGIN_GIT_WPLUS'));


        // Notified emails
        $driver->setReturnValue('getPostReceiveMailManager', new MockGit_PostReceiveMailManager());

        $driver->dumpProjectRepoConf($prj);

        // Check every thing was commited
        $this->assertEmptyGitStatus();

        // Ensure file is correct
        $result     = file_get_contents($this->_glAdmDir.'/conf/projects/project1.conf');
        $this->assertPattern("%$project_name/$repo_name = \"$repo_description\"%", $result);
    }
    
    public function ________________itReplacesNewlinesBySpaces() {
        $driver = partial_mock('Git_GitoliteDriver', array('getDao', 'getPostReceiveMailManager'), array($this->_glAdmDir));
        //$driver->setAdminPath($this->_glAdmDir);
        $repo_description = 'Vive 
            tuleap';
        $repo_name        = 'test_default';
        $project_name     = 'project1';

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', $project_name);

        // List all repo
        $dao = stub('GitDao')->getAllGitoliteRespositories()->returnsDar(array('repository_id' => 4, 
                    'repository_name' => $repo_name, 
                    'repository_namespace' => '', 
                    'repository_events_mailing_prefix' => "[SCM]", 
                    'repository_description' => $repo_description));
        $driver->setReturnValue('getDao', $dao);
        
        $permissions_manager = $this->permissions_manager;
        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('2'),   array(4, 'PLUGIN_GIT_READ'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('3'),   array(4, 'PLUGIN_GIT_WRITE'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(),      array(4, 'PLUGIN_GIT_WPLUS'));


        // Notified emails
        $driver->setReturnValue('getPostReceiveMailManager', new MockGit_PostReceiveMailManager());

        $driver->dumpProjectRepoConf($prj);

        // Check every thing was commited
        $this->assertEmptyGitStatus();

        // Ensure file is correct
        $result     = file_get_contents($this->_glAdmDir.'/conf/projects/project1.conf');
        $this->assertPattern("%$project_name/$repo_name = \"Vive tuleap\"%", $result);
    }

    
    //
    // The project has 2 repositories nb 4 & 5.
    // 4 has defaults
    // 5 has pimped perms
    public function testDumpProjectRepoPermissions() {
        $driver = partial_mock('Git_GitoliteDriver', array('getDao', 'getPostReceiveMailManager'), array($this->_glAdmDir));
        //$driver->setAdminPath($this->_glAdmDir);

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        $dao = stub('GitDao')->getAllGitoliteRespositories()->once()->returnsDar(
                array('repository_id' => 4, 'repository_name' => 'test_default', 'repository_namespace' => '', 'repository_events_mailing_prefix' => "[SCM]", 'repository_description' => ''),
                array('repository_id' => 5, 'repository_name' => 'test_pimped', 'repository_namespace' => '', 'repository_events_mailing_prefix' => "[KOIN] ", 'repository_description' => ''));
        $driver->setReturnValue('getDao', $dao);
        
        $permissions_manager = $this->permissions_manager;
        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('2'),   array(4, 'PLUGIN_GIT_READ'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('3'),   array(4, 'PLUGIN_GIT_WRITE'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(),      array(4, 'PLUGIN_GIT_WPLUS'));

        // Repo 5 (test_pimped): R = project_members | W = project_admin | W+ = user groups 101
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('3'),   array(5, 'PLUGIN_GIT_READ'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('4'),   array(5, 'PLUGIN_GIT_WRITE'));
        $permissions_manager->setReturnValue('getAuthorizedUgroupIds', array('125'), array(5, 'PLUGIN_GIT_WPLUS'));

        // Notified emails
        $notifMgr = new MockGit_PostReceiveMailManager();
        $notifMgr->setReturnValue('getNotificationMailsByRepositoryId', array('john.doe@enalean.com', 'mme.michue@enalean.com'), array(4));
        $notifMgr->setReturnValue('getNotificationMailsByRepositoryId', array(), array(5));
        $driver->setReturnValue('getPostReceiveMailManager', $notifMgr);

        $driver->dumpProjectRepoConf($prj);

        // Check every thing was commited
        $this->assertEmptyGitStatus();

        // Ensure file is correct
        $result     = file_get_contents($this->_glAdmDir.'/conf/projects/project1.conf');
        $expected   = file_get_contents($this->_fixDir .'/perms/project1-full.conf');
        $this->assertIdentical($expected, $result);

        // Check that corresponding project conf exists in main file conf
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/gitolite.conf'));
        $gitoliteConf = $this->getGitoliteConf();
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }
    
    public function testRepoFullNameConcats_UnixProjectName_Namespace_And_Name() {
        $unix_name = 'project1';
        
        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', 'toto');
        $this->assertEqual('project1/toto/repo', $this->driver->repoFullName($repo, $unix_name));
        
        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', '');
        $this->assertEqual('project1/repo', $this->driver->repoFullName($repo, $unix_name));
    }    

    public function itCanRenameProject() {
        $this->gitExec->expectOnce('push');
        
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));
        
        $this->assertTrue($this->driver->renameProject('legacy', 'newone'));
        
        clearstatcache(true, $this->_glAdmDir.'/conf/projects/legacy.conf');
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));
        $this->assertIdentical(
            file_get_contents($this->_fixDir.'/perms/newone.conf'),
            file_get_contents($this->_glAdmDir.'/conf/projects/newone.conf')
        );
        $this->assertNoPattern('`\ninclude "projects/legacy.conf"\n`', $this->getGitoliteConf());
        $this->assertPattern('`\ninclude "projects/newone.conf"\n`', $this->getGitoliteConf());
        $this->assertEmptyGitStatus();
    }
}

class Git_GitoliteDriver_ForkTest extends GitoliteTestCase {

    public function testFork_CloneEmptyToSpecifiedPath() {

        if (posix_getgrnam('gitolite') == false) {
            echo "testFork_CloneEmptyToSpecifiedPath: Cannot test 'cause there is no 'gitolite' user on server (CI)";
        } else {
            $name = 'tulip';
            $new_ns = 'repos/new/repo/';
            $old_ns = 'repos/';
            $old_root_dir = $this->repoDir .'/'. $old_ns . $name .'.git';
            $new_root_dir = $this->repoDir .'/'. $new_ns . $name .'.git';

            mkdir($old_root_dir, 0770, true);
            exec('GIT_DIR='. $old_root_dir .' git init --bare --shared=group');
            exec('cd '.$old_root_dir.' && touch hooks/gitolite_hook.sh');

            $this->assertTrue($this->driver->fork($name, $old_ns, $new_ns));
            $this->assertRepoIsClonedWithHooks($new_root_dir);

            $this->assertWritableByGroup($new_root_dir, 'gitolite');
            $this->assertNameSpaceFileHasBeenInitialized($new_root_dir, $new_ns, 'gitolite');
        }

    }
    
    public function testForkShouldNotCloneOnExistingRepositories() {
        $name = 'tulip';
        $new_ns = 'repos/new/repo/';
        $old_ns = 'repos/';
        $old_root_dir = $this->repoDir .'/'. $old_ns . $name .'.git';
        $new_root_dir = $this->repoDir .'/'. $new_ns . $name .'.git';
        
        mkdir($old_root_dir, 0770, true);
        exec('GIT_DIR='. $old_root_dir .' git --bare init --shared=group');
        
        mkdir($new_root_dir, 0770, true);
        exec('GIT_DIR='. $new_root_dir .' git --bare init --shared=group');
        
        $this->assertFalse($this->driver->fork($name, $old_ns, $new_ns));
    }
    
    
    // JM: Dont understant this test, should it be in _Fork or the miscallaneous part?
    public function itIsInitializedEvenIfThereIsNoMaster() {
        $this->assertTrue($this->driver->isInitialized($this->_fixDir.'/headless.git'));
    }
    
    public function itIsNotInitializedldIfThereIsNoValidDirectory() {
        $this->assertFalse($this->driver->isInitialized($this->_fixDir));
    }
}

?>