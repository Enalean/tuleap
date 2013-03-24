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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Git_GitoliteTestCase.class.php';

class Git_Gitolite_SshKeyTestCase extends Git_GitoliteTestCase {
    protected $key1;
    protected $key2;

    public function setUp() {
        parent::setUp();
        $this->key1 = 'ssh-rsa AAAAYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $this->key2 = 'ssh-rsa AAAAXYiTICSgWURDPDGW/HeNUYZIRcznQ== marcel@shanon.net';
        chdir('/var');
    }
}

class Git_Gitolite_SSHKeyDumper_OneUserTest extends Git_Gitolite_SshKeyTestCase {

    public function testAddUserKey() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1))->build();

        $this->dumper->dumpSSHKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testAddUserWithSeveralKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();

        $this->dumper->dumpSSHKeys($user);

        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $this->key1);
        $this->assertTrue(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@1.pub'), $this->key2);

        $this->assertEmptyGitStatus();
    }

    public function testRemoveUserKey() {
        // User has 2 keys
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->dumper->dumpSSHKeys($user);

        // Now back with only one
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1))->build();
        $this->dumper->dumpSSHKeys($user);

        // Ensure second key was deleted
        $this->assertFalse(is_file($this->_glAdmDir.'/keydir/john_do@1.pub'), "Second key should be deleted");

        $this->assertEmptyGitStatus();
    }

    public function itDeletesAllTheKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->dumper->dumpSSHKeys($user);

        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array())->build();
        $this->dumper->dumpSSHKeys($user);
        $this->assertCount(glob($this->_glAdmDir.'/keydir/*.pub'), 0);

        $this->assertEmptyGitStatus();
    }

    public function itFlipsTheKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->dumper->dumpSSHKeys($user);

        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key2, $this->key1))->build();
        $this->dumper->dumpSSHKeys($user);
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@0.pub'), $this->key2);
        $this->assertEqual(file_get_contents($this->_glAdmDir.'/keydir/john_do@1.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function itDoesntGenerateAnyErrorsWhenThereAreNoChangesOnKeys() {
        $user = aUser()->withUserName('john_do')->withAuthorizedKeysArray(array($this->key1, $this->key2))->build();
        $this->dumper->dumpSSHKeys($user);

        $this->dumper->dumpSSHKeys($user);
    }

    public function itRemovesTheKeysWhenUserNoLongerHaveOneDuringSystemCheck() {

    }
}


?>
