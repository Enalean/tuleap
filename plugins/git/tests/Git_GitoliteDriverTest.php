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
require_once 'Git_GitoliteTestCase.class.php';

class Git_GitoliteDriverTest extends Git_GitoliteTestCase {
    
    protected function _GivenARepositoryWithNameAndNamespace($name, $namespace) {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

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

    public function itAddsTheDescriptionToTheConfFile() {
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
                    'repository_description' => $repo_description,
                    'remote_server_id' => null));
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
    
    public function itReplacesNewlinesBySpaces() {
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
                    'repository_description' => $repo_description,
                    'remote_server_id' => null));
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
            array(
                'repository_id'                    => 4,
                'repository_name'                  => 'test_default',
                'repository_namespace'             => '',
                'repository_events_mailing_prefix' => "[SCM]",
                'repository_description'           => '',
                'remote_server_id'                 => null
            ),
            array(
                'repository_id'                    => 5,
                'repository_name'                  => 'test_pimped',
                'repository_namespace'             => '',
                'repository_events_mailing_prefix' => "[KOIN] ",
                'repository_description'           => '',
                'remote_server_id'                 => null
            )
        );
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

    public function testRewindAccessRightsToGerritUserWhenRepoIsMigratedToGerrit() {
        $driver = partial_mock('Git_GitoliteDriver', array('getDao', 'getPostReceiveMailManager'), array($this->_glAdmDir));

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        $dao = stub('GitDao')->getAllGitoliteRespositories()->once()->returnsDar(
            array(
                'repository_id'                    => 4,
                'repository_name'                  => 'before_migration_to_gerrit',
                'repository_namespace'             => '',
                'repository_events_mailing_prefix' => "[SCM]",
                'repository_description'           => '',
                'remote_server_id'                 => null,
                'remote_project_deleted_date'      => null,
                'remote_server_disconnect_date'    => null
            ),
            array(
                'repository_id'                    => 5,
                'repository_name'                  => 'after_migration_to_gerrit',
                'repository_namespace'             => '',
                'repository_events_mailing_prefix' => "[SCM]",
                'repository_description'           => '',
                'remote_server_id'                 => 1,
                'remote_project_deleted_date'      => null,
                'remote_server_disconnect_date'    => null
            )
        );
        $driver->setReturnValue('getDao', $dao);

        $permissions_manager = $this->permissions_manager;
        stub($this->permissions_manager)->getAuthorizedUgroupIds(4, 'PLUGIN_GIT_READ')->returns(array('2'));
        stub($this->permissions_manager)->getAuthorizedUgroupIds(4, 'PLUGIN_GIT_WRITE')->returns(array('3'));
        stub($this->permissions_manager)->getAuthorizedUgroupIds(4, 'PLUGIN_GIT_WPLUS')->returns(array('125'));
        stub($this->permissions_manager)->getAuthorizedUgroupIds(5, 'PLUGIN_GIT_READ')->returns(array('2'));
        stub($this->permissions_manager)->getAuthorizedUgroupIds(5, 'PLUGIN_GIT_WRITE')->returns(array('3'));
        stub($this->permissions_manager)->getAuthorizedUgroupIds(5, 'PLUGIN_GIT_WPLUS')->returns(array('125'));

        // Notified emails
        $notifMgr = new MockGit_PostReceiveMailManager();
        $driver->setReturnValue('getPostReceiveMailManager', $notifMgr);

        $driver->dumpProjectRepoConf($prj);

        // Ensure file is correct
        $result   = file_get_contents($this->_glAdmDir.'/conf/projects/project1.conf');
        $expected = file_get_contents($this->_fixDir .'/perms/migrated_to_gerrit.conf');
        $this->assertIdentical($expected, $result);
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

class Git_GitoliteDriver_ForkTest extends Git_GitoliteTestCase {
    
    protected function _getFileGroupName($filePath) {
        clearstatcache();
        $rootStats = stat($filePath);
        $groupInfo = posix_getgrgid($rootStats[5]);
        return $groupInfo['name'];
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
    
    public function assertRepoIsClonedWithHooks($new_root_dir) {
        $this->assertTrue(is_dir($new_root_dir), "the new git repo dir ($new_root_dir) wasn't found.");
        $new_repo_HEAD = $new_root_dir . '/HEAD';
        $this->assertTrue(file_exists($new_repo_HEAD), 'the file (' . $new_repo_HEAD . ') does not exists');
        $this->assertTrue(file_exists($new_root_dir . '/hooks/gitolite_hook.sh'), 'the hook file wasn\'t copied to the fork');
    }

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