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

require_once dirname(__FILE__).'/../include/Git.class.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';

Mock::generate('Project');
Mock::generate('User');
Mock::generate('GitDao');
Mock::generate('PermissionsManager');
Mock::generate('DataAccessResult');
Mock::generate('Git_PostReceiveMailManager');

class GitoliteTestCase extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->cwd           = getcwd();
        $this->_fixDir       = dirname(__FILE__).'/_fixtures';
        $this->_tmpDir       = '/tmp';
        $this->_glAdmDirRef  = $this->_tmpDir.'/gitolite-admin-ref';
        $this->_glAdmDir     = $this->_tmpDir.'/gitolite-admin';
    
        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        system('tar -xf '. $this->_fixDir.'/gitolite-admin-ref' .'.tar --directory '.$this->_tmpDir);
        symlink($this->_glAdmDirRef, $this->_glAdmDir);
    
        $this->httpsHost = $GLOBALS['sys_https_host'];
    
        $GLOBALS['sys_https_host'] = 'localhost';
        PermissionsManager::setInstance(new MockPermissionsManager());
    }
    
    public function tearDown() {
        parent::tearDown();
        chdir($this->cwd);
    
        system('rm -rf '. $this->_glAdmDirRef);
        system('rm -rf '. $this->_glAdmDir .'/repositories/*');
        unlink($this->_glAdmDir);
        $GLOBALS['sys_https_host'] = $this->httpsHost;
        PermissionsManager::clearInstance();
    }
    
    public function assertEmptyGitStatus() {
        exec('git status --porcelain', $output, $ret_val);
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

    public function getPartialMock($className, $methods) {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName();
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

class Git_GitoliteDriverTest extends GitoliteTestCase {
    
    public function testGitoliteConfUpdate() {
        // Test base: one gitolite conf + 1 project file
        file_put_contents($this->_tmpDir.'/gitolite-admin/conf/gitolite.conf', '@test = coin'.PHP_EOL);
        touch($this->_tmpDir.'/gitolite-admin/conf/projects/project1.conf');
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $driver = new Git_GitoliteDriver($this->_tmpDir.'/gitolite-admin');
        $driver->updateMainConfIncludes($prj);

        $gitoliteConf = file_get_contents($this->_tmpDir.'/gitolite-admin/conf/gitolite.conf');
        // Original content still here
        $this->assertWantedPattern('#^@test = coin$#m', $gitoliteConf);
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function testAddUserKey() {
        $key = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9HtaylfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVrqH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9ZyT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHnQQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YLRp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== codendiadm@dev';
        $user = new MockUser($this);
        $user->setReturnValue('getUserName', 'john_do');
        $user->expectOnce('getAuthorizedKeys', array(true));
        $user->setReturnValue('getAuthorizedKeys', array($key));

        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        $driver->initUserKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $key);

        $this->assertEmptyGitStatus();
    }

    public function testaddUserWithSeveralKeys() {
        $key1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9HtaylfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVrqH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9ZyT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHnQQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YLRp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $key2 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA00qxJHrLEbrVTEtvC9c7xaeNIV81vxns7T89tGmyocFlPeD2N+uUQ8J90bcv7+aQDo229EWWI7oV6uGqsFXAuWSHHSvl7Am+2/lzVwSkvrVYAKl26Kz505a+W9xMbMKn8B+LFuOg3sjUKeVuz0WiUuKnHhhJUEBW+mJtuHrow49+6mOuL5v+M+0FlwGthagQt1zjWvo6g8GC4x97Wt3FVu8cfQJVu7S5KBXiz2VjRAwKTovt+M4+PlqO00vWbaaviFirwJPXjHoGVKONa/ahrXYiTICSgWUR6CjlqHs15cMSFOfkmDimu9KJiaOvfMNDPDGW/HeNUYB7HqYZIRcznQ== marcel@shanon.net';
        $user = new MockUser($this);
        $user->setReturnValue('getUserName', 'john_do');
        $user->expectOnce('getAuthorizedKeys', array(true));
        $user->setReturnValue('getAuthorizedKeys', array($key1, $key2));

        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        $driver->initUserKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $key1);
        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@1.pub'), $key2);

        $this->assertEmptyGitStatus();
    }

    public function testRemoveUserKey() {
        // run previous test to have several keys
        $this->testaddUserWithSeveralKeys();

        // Now back with only one
        $this->testAddUserKey();

        // Ensure second key was deleted
        $this->assertFalse(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'), "Second key should be deleted");

        $this->assertEmptyGitStatus();
    }
    
    public function testGetMailHookConfig() {
        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        
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
            $driver->fetchMailHookConfig($prj, $repo)
        );

        // ShowRev + Mail
        $repo = new GitRepository();
        $repo->setId(5);
        $repo->setProject($prj);
        $repo->setName('test_default');
        $repo->setNotifiedMails(array('john.doe@enalean.com', 'mme.michue@enalean.com'));
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail.txt'),
            $driver->fetchMailHookConfig($prj, $repo)
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
            $driver->fetchMailHookConfig($prj, $repo)
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
            $driver->fetchMailHookConfig($prj, $repo)
        );
    }

    //
    // The project has 2 repositories nb 4 & 5.
    // 4 has defaults
    // 5 has pimped perms
    public function testDumpProjectRepoPermissions() {
        $driver = $this->getPartialMock('Git_GitoliteDriver', array('getDao', 'getPostReceiveMailManager'));
        $driver->setAdminPath($this->_glAdmDir);

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories', array(404));
        $dao->setReturnValue('getAllGitoliteRespositories', $this->arrayToDar(array('repository_id' => 4, 'repository_name' => 'test_default', 'repository_namespace' => '', 'repository_events_mailing_prefix' => "[SCM]"),
                                                                              array('repository_id' => 5, 'repository_name' => 'test_pimped', 'repository_namespace' => '', 'repository_events_mailing_prefix' => "[KOIN] ")
                                                            )
        );
        $driver->setReturnValue('getDao', $dao);
        
        $permissions_manager = PermissionsManager::instance();
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
        $this->assertTrue(is_file($this->_tmpDir.'/gitolite-admin/conf/gitolite.conf'));
        $gitoliteConf = file_get_contents($this->_tmpDir.'/gitolite-admin/conf/gitolite.conf');
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }
    
    public function testRepoFullNameConcats_UnixProjectName_Namespace_And_Name() {
        $driver = new Git_GitoliteDriver();
        $unix_name = 'project1';
        
        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', 'toto');
        $this->assertEqual('project1/toto/repo', $driver->repoFullName($repo, $unix_name));
        
        $repo = $this->_GivenARepositoryWithNameAndNamespace('repo', '');
        $this->assertEqual('project1/repo', $driver->repoFullName($repo, $unix_name));
    }    

    public function itCanRenameProject() {
        $driver = $this->getPartialMock('Git_GitoliteDriver', array('gitPush'));
        $driver->expectOnce('gitPush');
        $driver->setReturnValue('gitPush', true);
        $driver->setAdminPath($this->_glAdmDir);
        
        
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));
        
        $this->assertTrue($driver->renameProject('legacy', 'newone'));
        
        clearstatcache(true, $this->_glAdmDir.'/conf/projects/legacy.conf');
        $this->assertFalse(is_file($this->_glAdmDir.'/conf/projects/legacy.conf'));
        $this->assertTrue(is_file($this->_glAdmDir.'/conf/projects/newone.conf'));
        $this->assertIdentical(
            file_get_contents($this->_fixDir.'/perms/newone.conf'),
            file_get_contents($this->_glAdmDir.'/conf/projects/newone.conf')
        );
        $this->assertNoPattern('`\ninclude "projects/legacy.conf"\n`', file_get_contents($this->_glAdmDir.'/conf/gitolite.conf'));
        $this->assertPattern('`\ninclude "projects/newone.conf"\n`', file_get_contents($this->_glAdmDir.'/conf/gitolite.conf'));
        $this->assertEmptyGitStatus();
    }
    
    public function testFork_CloneEmptyToSpecifiedPath() {

        if (posix_getgrnam('gitolite') == false) {
            echo "testFork_CloneEmptyToSpecifiedPath: Cannot test 'cause there is no 'gitolite' user on server (CI)";
        } else {
            $repositoriesDir = $this->_tmpDir.'/repositories/';
            exec('rm -rf '  .$repositoriesDir);
            
            $name = 'tulip';
            $new_ns = 'repos/new/repo/';
            $old_ns = 'repos/';
            $old_root_dir = $repositoriesDir. $old_ns . $name .'.git';
            $new_root_dir = $repositoriesDir. $new_ns . $name .'.git';

            mkdir($old_root_dir, 0770, true);
            exec('GIT_DIR='. $old_root_dir .' git init --bare --shared=group');
            exec('cd '.$old_root_dir.' && touch hooks/gitolite_hook.sh');

            $driver = new Git_GitoliteDriver($this->_glAdmDir);

            $this->assertTrue($driver->fork($name, $old_ns, $new_ns));
            $this->assertRepoIsClonedWithHooks($new_root_dir);

            $this->assertWritableByGroup($new_root_dir, 'gitolite');
            $this->assertNameSpaceFileHasBeenInitialized($new_root_dir, $new_ns, 'gitolite');
            exec('rm -rf '.$repositoriesDir);
        }

    }
    
    public function testForkShouldNotCloneOnExistingRepositories() {
        $name = 'tulip';
        $new_ns = 'repos/new/repo/';
        $old_ns = 'repos/';
        $old_root_dir = $this->_tmpDir .'/repositories/'. $old_ns . $name .'.git';
        $new_root_dir = $this->_tmpDir .'/repositories/'. $new_ns . $name .'.git';
        
        mkdir($old_root_dir, 0770, true);
        exec('GIT_DIR='. $old_root_dir .' git --bare init --shared=group');
        
        mkdir($new_root_dir, 0770, true);
        exec('GIT_DIR='. $new_root_dir .' git --bare init --shared=group');
        
        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        $this->assertFalse($driver->fork($name, $old_ns, $new_ns));
    }
    
    public function itIsInitializedEvenIfThereIsNoMaster() {
        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        $this->assertTrue($driver->isInitialized($this->_fixDir.'/headless.git'));
    }
    
    public function itIsNotInitializedldIfThereIsNoValidDirectory() {
        $driver = new Git_GitoliteDriver($this->_glAdmDir);
        $this->assertFalse($driver->isInitialized($this->_fixDir));
    }
}

?>