<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__.'/Git_GitoliteTestCase.class.php';
require_once 'bootstrap.php';

class Git_Gitolite_SSHKeyDumper_AllUsersTest extends Git_GitoliteTestCase
{
    protected $mass_dumper;
    protected $key1;
    protected $key2;

    public function setUp()
    {
        parent::setUp();
        $this->key1 = 'ssh-rsa AAAAYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $this->key2 = 'ssh-rsa AAAAXYiTICSgWURDPDGW/HeNUYZIRcznQ== marcel@shanon.net';
        chdir('/var');

        $this->mass_dumper = new Git_Gitolite_SSHKeyMassDumper($this->dumper, $this->user_manager);
    }

    public function itDumpsSshKeysForOneUser()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        stub($this->user_manager)->getUsersWithSshKey()->returnsDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')));

        expect($this->gitExec)->push()->once();
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir . '/keydir/john_do@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function itRemovesSshKeyFileWhenUserDeletedAllHisKeys()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        expect($this->gitExec)->push()->count(2);

        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
    }

    public function itRemovesOnlySshFilesForUsersWithoutKeys()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/do_john@0.pub'));

        $this->assertEmptyGitStatus();
    }

    public function itRemovesSshFilesWhenKeysAreDeleted()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2 . PFUser::SSH_KEY_SEPARATOR . $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/do_john@1.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/do_john@0.pub'));
        $this->assertEqual(file_get_contents($this->_glAdmDir . '/keydir/do_john@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function itDoesntRemoveTheGitoliteAdminSSHKey()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        touch($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub');
        $this->gitExec->add($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub');
        $this->gitExec->commit("Admin key");
        $this->assertEmptyGitStatus();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/id_rsa_gl-adm.pub'));
    }

    public function itDoesntRemoveTheGerritReservedKeys()
    {
        $invalid_keys_collector = mock('Tuleap\\Git\\Gitolite\\SSHKey\\InvalidKeysCollector');
        $this->user_manager->setReturnValueAt(0, 'getUsersWithSshKey', TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $keyfile = 'forge__gerrit_1@0.pub';
        touch($this->_glAdmDir . '/keydir/'.$keyfile);
        $this->gitExec->add($this->_glAdmDir . '/keydir/'.$keyfile);
        $this->gitExec->commit("Gerrit key");
        $this->assertEmptyGitStatus();

        $this->user_manager->setReturnValueAt(1, 'getUsersWithSshKey', TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->_glAdmDir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->_glAdmDir . '/keydir/'.$keyfile));
    }
}
