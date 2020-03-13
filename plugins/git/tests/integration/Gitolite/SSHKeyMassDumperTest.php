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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use Git_Gitolite_SSHKeyMassDumper;
use PFUser;
use TestHelper;
use Tuleap\GlobalLanguageMock;

final class SSHKeyMassDumperTest extends \Tuleap\Git\Gitolite\GitoliteTestCase
{
    use GlobalLanguageMock;

    private $mass_dumper;
    private $key1;
    private $key2;

    protected function setUp() : void
    {
        parent::setUp();
        $this->key1 = 'ssh-rsa AAAAYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $this->key2 = 'ssh-rsa AAAAXYiTICSgWURDPDGW/HeNUYZIRcznQ== marcel@shanon.net';

        $this->mass_dumper = new Git_Gitolite_SSHKeyMassDumper($this->dumper, $this->user_manager);
    }

    public function testItDumpsSshKeysForOneUser() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->user_manager->shouldReceive('getUsersWithSshKey')->andReturns(\TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));

        $this->git_exec->shouldReceive('push')->once();
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertTrue(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        $this->assertEquals(file_get_contents($this->gitolite_admin_dir . '/keydir/john_do@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testItRemovesSshKeyFileWhenUserDeletedAllHisKeys() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->git_exec->shouldReceive('push')->times(2);

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
    }

    public function testItRemovesOnlySshFilesForUsersWithoutKeys() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->git_exec->shouldReceive('push')->andReturn(true);
        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key2, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->gitolite_admin_dir . '/keydir/do_john@0.pub'));

        $this->assertEmptyGitStatus();
    }

    public function testItRemovesSshFilesWhenKeysAreDeleted() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->git_exec->shouldReceive('push')->andReturn(true);
        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do')), new PFUser(array('authorized_keys' => $this->key2 . PFUser::SSH_KEY_SEPARATOR . $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'do_john'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/do_john@1.pub'));
        $this->assertTrue(is_file($this->gitolite_admin_dir . '/keydir/do_john@0.pub'));
        $this->assertEquals(file_get_contents($this->gitolite_admin_dir . '/keydir/do_john@0.pub'), $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testItDoesntRemoveTheGitoliteAdminSSHKey() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->git_exec->shouldReceive('push')->andReturn(true);
        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        touch($this->gitolite_admin_dir . '/keydir/id_rsa_gl-adm.pub');
        $this->git_exec->add($this->gitolite_admin_dir . '/keydir/id_rsa_gl-adm.pub');
        $this->git_exec->commit("Admin key");
        $this->assertEmptyGitStatus();

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->gitolite_admin_dir . '/keydir/id_rsa_gl-adm.pub'));
    }

    public function testItDoesntRemoveTheGerritReservedKeys() : void
    {
        $invalid_keys_collector = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector::class);
        $this->git_exec->shouldReceive('push')->andReturn(true);
        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::arrayToDar(new PFUser(array('authorized_keys' => $this->key1, 'user_name' => 'john_do'))));
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $keyfile = 'forge__gerrit_1@0.pub';
        touch($this->gitolite_admin_dir . '/keydir/' . $keyfile);
        $this->git_exec->add($this->gitolite_admin_dir . '/keydir/' . $keyfile);
        $this->git_exec->commit("Gerrit key");
        $this->assertEmptyGitStatus();

        $this->user_manager->shouldReceive('getUsersWithSshKey')->once()->andReturns(TestHelper::emptyDar());
        $this->mass_dumper->dumpSSHKeys($invalid_keys_collector);

        $this->assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        $this->assertTrue(is_file($this->gitolite_admin_dir . '/keydir/' . $keyfile));
    }
}
