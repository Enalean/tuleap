<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/GitoliteDriver.class.php';

Mock::generate('Project');
Mock::generate('User');

class GitoliteDriverTest extends UnitTestCase {

    function setUp() {
        $this->cwd = getcwd();
        $this->_fixDir = dirname(__FILE__).'/_fixtures';
        $this->_glAdmDir = $this->_fixDir.'/gitolite-admin';
        mkdir($this->_glAdmDir);
        mkdir($this->_glAdmDir.'/conf');
        touch($this->_glAdmDir.'/conf/gitolite.conf');
        chdir($this->_glAdmDir);
        system('git init -q && git add conf/gitolite.conf && git commit -m "init" 2>&1 >/dev/null');
    }

    function tearDown() {
        chdir($this->cwd);
        system('rm -rf '.$this->_glAdmDir);
    }

    function assertEmptyGitStatus() {
        exec('git status --porcelain', $output, $ret_val);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }

    function testCreateRepository() {
        $driver = new Git_GitoliteDriver($this->_fixDir.'/gitolite-admin');

        $prj = new MockProject($this);
        $prj->setReturnValue('getUnixName', 'project1');

        $this->assertTrue($driver->init($prj, 'testrepo'));

        $this->assertEmptyGitStatus();

        // Check file content
        $this->assertTrue(is_file($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf'));
        $gitoliteConf = file($this->_fixDir.'/gitolite-admin/conf/projects/project1.conf', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Check repository def
        $repo1found = false;
        for ($i = 0; $i < count($gitoliteConf); $i++) {
            if ($gitoliteConf[$i] == 'repo project1/testrepo') {
                $repo1found = true;
                // Check default permissions
                $this->assertEqual($gitoliteConf[++$i], "\tRW = @project1_project_members");
            }
        }
        $this->assertTrue($repo1found);

        // Check that corresponding project conf exists in main file conf
        $this->assertTrue(is_file($this->_fixDir.'/gitolite-admin/conf/gitolite.conf'));
        $gitoliteConf = file_get_contents($this->_fixDir.'/gitolite-admin/conf/gitolite.conf');
        $this->assertWantedPattern('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

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
        $user->setReturnValue('getAuthorizedKeys', $key);

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
        $user->setReturnValue('getAuthorizedKeys', $key1."######".$key2);

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
}

?>