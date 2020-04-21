<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_SSHKeyDumperTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\ForgeConfigSandbox;

    private $toto_name;
    private $toto_home;
    private $foobar_home;
    private $key;
    private $user;
    private $sshkey_dumper;
    private $backend;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('homedir_prefix', $this->getTmpDir());
        ForgeConfig::set('codendi_log', $this->getTmpDir());
        $this->toto_name = 'toto';
        $this->toto_home = ForgeConfig::get('homedir_prefix') . '/' . $this->toto_name;
        mkdir($this->toto_home);

        $this->foobar_home = ForgeConfig::get('homedir_prefix') . '/foobar';
        mkdir($this->foobar_home . '/.ssh', 0751, true);
        touch($this->foobar_home . '/.ssh/authorized_keys');

        $this->backend = \Mockery::mock(\Backend::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->key  = 'bla';
        $this->user = new PFUser([
            'user_name'       => $this->toto_name,
            'authorized_keys' => $this->key,
            'unix_status'     => 'A',
            'language_id'     => 'en_US'
        ]);
        $this->sshkey_dumper = \Mockery::mock(\User_SSHKeyDumper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->sshkey_dumper->__construct($this->backend);
    }

    public function testItDoesntWriteTheKeyWhenUserAsNotAValidUnixAccount(): void
    {
        $this->backend->shouldReceive('log')->never();
        $this->user->setUnixStatus('S');
        $this->sshkey_dumper->writeSSHKeys($this->user);
        $this->assertFalse(is_file($this->toto_home . '/.ssh/authorized_keys'));
    }

    public function testItWriteTheKeyInTheAutorizedKeyFile(): void
    {
        $this->backend->shouldReceive('log')->with(
            Hamcrest\Matchers::matchesPattern('/Authorized_keys for ' . $this->toto_name . ' written/'),
            'info'
        )->once();

        $this->backend->shouldReceive('chown')->with($this->toto_home . '/.ssh', $this->toto_name)->once();
        $this->backend->shouldReceive('chown')->with($this->toto_home . '/.ssh/authorized_keys', $this->toto_name)->once();

        $this->backend->shouldReceive('chgrp')->with($this->toto_home . '/.ssh', $this->toto_name)->once();
        $this->backend->shouldReceive('chgrp')->with($this->toto_home . '/.ssh/authorized_keys', $this->toto_name)->once();

        $this->sshkey_dumper->shouldReceive('changeProcessUidGidToUser')->once();
        $this->sshkey_dumper->shouldReceive('restoreRootUidGid')->once();

        $this->sshkey_dumper->writeSSHKeys($this->user);
        $this->assertEquals($this->key, file_get_contents($this->toto_home . '/.ssh/authorized_keys'));
        $this->assertEquals('0700', $this->getFileModeAsString($this->toto_home . '/.ssh'));
        $this->assertEquals('0600', $this->getFileModeAsString($this->toto_home . '/.ssh/authorized_keys'));
    }

    public function testItDoesntModifyFilesWhenUserMadeASymlink(): void
    {
        // The user tries to compromise the system, it has an SSH account on the
        // server and made a link from its own authorized_keys file onto someoneelse
        // example /home/users/toto/.ssh/authorized_keys -> /root/.ssh/authorized_keys
        // if following test fails, it means that a mere user can use it to ssh
        // the machine as root!
        mkdir($this->toto_home . '/.ssh', 0751, true);
        symlink($this->foobar_home . '/.ssh/authorized_keys', $this->toto_home . '/.ssh/authorized_keys');

        $this->sshkey_dumper->shouldReceive('changeProcessUidGidToUser')->once();
        $this->sshkey_dumper->shouldReceive('restoreRootUidGid')->once();
        $this->backend->shouldReceive('changeOwnerGroupMode')->twice();


        $this->sshkey_dumper->writeSSHKeys($this->user);
        $this->assertEquals($this->key, file_get_contents($this->toto_home . '/.ssh/authorized_keys'));
        $this->assertEquals('', file_get_contents($this->foobar_home . '/.ssh/authorized_keys'));
        $this->assertFalse(is_link($this->toto_home . '/.ssh/authorized_keys'));
        $this->assertFalse(is_link($this->foobar_home . '/.ssh/authorized_keys'));
    }

    public function testItRaisesAnErrorWhenUserAttemptedToMakeALinkOnSshDir(): void
    {
        // variation of previous test but user did:
        // /home/users/toto/.ssh -> /root/.ssh
        $foobar_ssh = $this->foobar_home . '/.ssh';
        symlink($foobar_ssh, $this->toto_home . '/.ssh');

        $this->sshkey_dumper->shouldReceive('changeProcessUidGidToUser')->once();
        $this->sshkey_dumper->shouldReceive('restoreRootUidGid')->once();

        $this->backend->shouldReceive('log')->with(
            Hamcrest\Matchers::matchesPattern('%was a link to "' . $foobar_ssh . '"%'),
            'error'
        )->once();

        $this->sshkey_dumper->writeSSHKeys($this->user);
    }

    public function testItDoesntModifyDirectoriesWhenUserMadeASymlink(): void
    {
        // variation of previous test but user did:
        // /home/users/toto/.ssh -> /root/.ssh
        symlink($this->foobar_home . '/.ssh', $this->toto_home . '/.ssh');

        $this->sshkey_dumper->shouldReceive('changeProcessUidGidToUser')->twice();
        $this->sshkey_dumper->shouldReceive('restoreRootUidGid')->times(3);

        // First call will fail (see previous test) ...
        $this->sshkey_dumper->writeSSHKeys($this->user);

        $this->assertFalse(is_link($this->toto_home . '/.ssh'));
        $this->assertFalse(is_link($this->foobar_home . '/.ssh'));

        // ... so execute twice to see is things are properly cleaned-up
        $this->sshkey_dumper->writeSSHKeys($this->user);

        $this->assertEquals($this->key, file_get_contents($this->toto_home . '/.ssh/authorized_keys'));
        $this->assertEquals('', file_get_contents($this->foobar_home . '/.ssh/authorized_keys'));
    }

    private function getFileModeAsString($filename)
    {
        return substr(sprintf('%o', fileperms($filename)), -4);
    }
}
