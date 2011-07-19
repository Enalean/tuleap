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

class Git_GitoliteDriverTest extends UnitTestCase {

    function __construct() {
        $this->_fixDir = dirname(__FILE__).'/_fixtures';

        $this->_glAdmDirRef = $this->_fixDir.'/gitolite-admin-ref';
        mkdir($this->_glAdmDirRef);
        mkdir($this->_glAdmDirRef.'/conf');
        touch($this->_glAdmDirRef.'/conf/gitolite.conf');
        $cwd = getcwd();
        chdir($this->_glAdmDirRef);
        system('git init -q && git add conf/gitolite.conf && git commit -m "init" 2>&1 >/dev/null');
        chdir($cwd);
    }

    function __destruct() {
        system('rm -rf '.$this->_glAdmDirRef);
    }

    function setUp() {
        $this->cwd = getcwd();
        $this->_glAdmDir = $this->_fixDir.'/gitolite-admin';

        system('cp -r '. $this->_glAdmDirRef .' '. $this->_glAdmDir);

        $this->httpsHost = $GLOBALS['sys_https_host'];
        $GLOBALS['sys_https_host'] = 'localhost';
    }

    function tearDown() {
        chdir($this->cwd);
        system('rm -rf '.$this->_glAdmDir);
        $GLOBALS['sys_https_host'] = $this->httpsHost;
    }

    function getPartialMock($className, $methods) {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName($this);
    }

    function arrayToDar() {
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

    function assertEmptyGitStatus() {
        exec('git status --porcelain', $output, $ret_val);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }
/*
    function testGitoliteConfUpdate() {
        // Test base: one gitolite conf + 1 project file
        file_put_contents($this->_fixDir.'/gitolite-admin/conf/gitolite.conf', '@test = coin'.PHP_EOL);
        mkdir($this->_fixDir.'/gitolite-admin/conf/projects');
        touch($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf');
        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $driver = new Git_GitoliteDriver($this->_fixDir.'/gitolite-admin');
        $driver->updateMainConfIncludes($prj);

        $gitoliteConf = file_get_contents($this->_fixDir.'/gitolite-admin/conf/gitolite.conf');
        // Original content still here
        $this->assertWantedPattern('#^@test = coin$#m', $gitoliteConf);
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    function testAddUserKey() {
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

    function testaddUserWithSeveralKeys() {
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

    function testRemoveUserKey() {
        // run previous test to have several keys
        $this->testaddUserWithSeveralKeys();

        // Now back with only one
        $this->testAddUserKey();

        // Ensure second key was deleted
        $this->assertFalse(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'), "Second key should be deleted");

        $this->assertEmptyGitStatus();
    }

    function testFetchPermissions() {
        $driver = new Git_GitoliteDriver($this->_glAdmDir);

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $ug_1 = 130;
        $ug_2 = 140;
        $ug_3 = 150;
        $ug_4 = 160;
        $ug_5 = 170;
        $ug_6 = 180;
        $ug_n = 100;

        $this->assertIdentical('',
            $driver->fetchPermissions($prj, array(), array(), array())
        );

        $this->assertIdentical('',
            $driver->fetchPermissions($prj, array($ug_n), array($ug_n), array($ug_n))
        );

        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-reader.conf'),
            $driver->fetchPermissions($prj, array($ug_1), array(), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-writer.conf'),
            $driver->fetchPermissions($prj, array(), array($ug_1), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/one-rewinder.conf'),
            $driver->fetchPermissions($prj, array(), array(), array($ug_1))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-readers.conf'),
            $driver->fetchPermissions($prj, array($ug_1, $ug_2), array(), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-writers.conf'),
            $driver->fetchPermissions($prj, array(), array($ug_1, $ug_2), array())
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/two-rewinders.conf'),
            $driver->fetchPermissions($prj, array(), array(), array($ug_1, $ug_2))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/full.conf'),
            $driver->fetchPermissions($prj, array($ug_1, $ug_2), array($ug_3, $ug_4), array($ug_5, $ug_6))
        );
        
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/perms/default.conf'),
            $driver->fetchPermissions($prj, array('2'), array('3'), array())
        );
    }

    function testGetMailHookConfig() {
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
        $repo->setMailPrefix('"[\_o<]" \t');
        $this->assertIdentical(
            file_get_contents($this->_fixDir .'/gitolite-mail-config/mailhook-rev-mail-prefix-quote.txt'),
            $driver->fetchMailHookConfig($prj, $repo)
        );
    }
*/
    /**
     * The project has 2 repositories nb 4 & 5.
     * 4 has defaults
     * 5 has pimped perms
     * 
     */
    function testDumpProjectRepoPermissions() {
        $driver = $this->getPartialMock('Git_GitoliteDriver', array('getPermissionsManager', 'getDao', 'getPostReceiveMailManager'));
        $driver->setAdminPath($this->_glAdmDir);

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');
        $prj->setReturnValue('getId', 404);

        // List all repo
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories', array(404));
        $dao->setReturnValue('getAllGitoliteRespositories', $this->arrayToDar(array('repository_id' => 4, 'repository_name' => 'test_default', 'repository_events_mailing_prefix' => "[SCM]"),
                                                                              array('repository_id' => 5, 'repository_name' => 'test_pimped', 'repository_events_mailing_prefix' => "[KOIN] ")
                                                            )
        );
        $driver->setReturnValue('getDao', $dao);

        $pm = new MockPermissionsManager();
        // Repo 4 (test_default): R = registered_users | W = project_members | W+ = none
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(array('ugroup_id' => '2')), array(4, 'PLUGIN_GIT_READ'));
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(array('ugroup_id' => '3')), array(4, 'PLUGIN_GIT_WRITE'));
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(), array(4, 'PLUGIN_GIT_WPLUS'));

        // Repo 5 (test_pimped): R = project_members | W = project_admin | W+ = user groups 101
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(array('ugroup_id' => '3')), array(5, 'PLUGIN_GIT_READ'));
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(array('ugroup_id' => '4')), array(5, 'PLUGIN_GIT_WRITE'));
        $pm->setReturnValue('getAuthorizedUgroups', $this->arrayToDar(array('ugroup_id' => '125')), array(5, 'PLUGIN_GIT_WPLUS'));
        $driver->setReturnValue('getPermissionsManager', $pm);

        // Notified emails
        $notifMgr = new MockGit_PostReceiveMailManager();
        $notifMgr->setReturnValue('getNotificationMailsByRepositoryId', array('john.doe@enalean.com', 'mme.michue@enalean.com'), array(4));
        $notifMgr->setReturnValue('getNotificationMailsByRepositoryId', array(), array(5));
        $driver->setReturnValue('getPostReceiveMailManager', $notifMgr);

        $driver->dumpProjectRepoConf($prj);

        // Check every thing was commited
        $this->assertEmptyGitStatus();

        // Ensure file is correct
        $this->assertIdentical(file_get_contents($this->_glAdmDir.'/conf/projects/project1.conf'), file_get_contents($this->_fixDir .'/perms/project1-full.conf'));

        // Check that corresponding project conf exists in main file conf
        $this->assertTrue(is_file($this->_fixDir.'/gitolite-admin/conf/gitolite.conf'));
        $gitoliteConf = file_get_contents($this->_fixDir.'/gitolite-admin/conf/gitolite.conf');
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }
}

?>