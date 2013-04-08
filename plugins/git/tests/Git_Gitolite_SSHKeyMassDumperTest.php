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
require_once 'Git_Gitolite_SSHKeyDumperTest.php';
require_once dirname(__FILE__).'/../include/Git_Gitolite_SSHKeyMassDumper.class.php';

class Git_Gitolite_SSHKeyDumper_AllUsersTest extends Git_Gitolite_SshKeyTestCase {
    protected $mass_dumper;

    public function setUp() {
        parent::setUp();
        $this->mass_dumper = new Git_Gitolite_SSHKeyMassDumper($this->dumper, $this->user_manager);
    }

    public function itDumpsSshKeysForOneUser() {
        stub($this->user_manager)->getUsersWithSshKey()->returnsDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')));

        $this->mass_dumper->dumpSSHKeys();

        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir . '/keydir/john_do@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function itRemovesSshKeyFileWhenUserDeletedAllHisKeys() {
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys();

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
    }

    public function itRemovesOnlySshFilesForUsersWithoutKeys() {
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys();

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/do_john@0.pub'));

        $this->assertEmptyGitStatus();
    }

    public function itRemovesSshFilesWhenKeysAreDeleted() {
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2 . PFUser::SSH_KEY_SEPARATOR . $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys();

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/do_john@1.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/do_john@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir . '/keydir/do_john@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function itDoesntRemoveTheGitoliteAdminSSHKey() {
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys();

        touch($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub');
        $this->gitExec->add($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub');
        $this->gitExec->commit("Admin key");
        $this->assertEmptyGitStatus();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys();

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub'));
    }

    public function itDoesntRemoveTheGerritReservedKeys() {
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys();

        $keyfile = 'forge__gerrit_1@0.pub';
        touch($this->_glAdmDir . '/keydir/'.$keyfile);
        $this->gitExec->add($this->_glAdmDir . '/keydir/'.$keyfile);
        $this->gitExec->commit("Gerrit key");
        $this->assertEmptyGitStatus();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys();

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/'.$keyfile));
    }
}

?>
