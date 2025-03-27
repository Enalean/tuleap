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

use PFUser;
use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SSHKeyDumperTest extends GitoliteTestCase
{
    protected string $key1;
    protected string $key2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->key1 = 'ssh-rsa AAAAYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $this->key2 = 'ssh-rsa AAAAXYiTICSgWURDPDGW/HeNUYZIRcznQ== marcel@shanon.net';
    }

    public function testAddUserKey(): void
    {
        $user                   = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1,
        ]);
        $invalid_keys_collector = new InvalidKeysCollector();

        $this->git_exec->expects($this->once())->method('push')->willReturn(true);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        self::assertTrue(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        self::assertStringEqualsFile($this->gitolite_admin_dir . '/keydir/john_do@0.pub', $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testAddUserWithSeveralKeys(): void
    {
        $user                   = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1 . PFUser::SSH_KEY_SEPARATOR . $this->key2,
        ]);
        $invalid_keys_collector = new InvalidKeysCollector();

        $this->git_exec->expects($this->once())->method('push')->willReturn(true);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        self::assertTrue(is_file($this->gitolite_admin_dir . '/keydir/john_do@0.pub'));
        self::assertStringEqualsFile($this->gitolite_admin_dir . '/keydir/john_do@0.pub', $this->key1);
        self::assertTrue(is_file($this->gitolite_admin_dir . '/keydir/john_do@1.pub'));
        self::assertStringEqualsFile($this->gitolite_admin_dir . '/keydir/john_do@1.pub', $this->key2);

        $this->assertEmptyGitStatus();
    }

    public function testRemoveUserKey(): void
    {
        $invalid_keys_collector = new InvalidKeysCollector();
        $this->git_exec->expects(self::exactly(2))->method('push')->willReturn(true);

        // User has 2 keys
        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1 . PFUser::SSH_KEY_SEPARATOR . $this->key2,
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        // Now back with only one
        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1,
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        // Ensure second key was deleted
        self::assertFalse(is_file($this->gitolite_admin_dir . '/keydir/john_do@1.pub'), 'Second key should be deleted');

        $this->assertEmptyGitStatus();
    }

    public function testItDeletesAllTheKeys(): void
    {
        $invalid_keys_collector = new InvalidKeysCollector();
        $this->git_exec->method('push')->willReturn(true);
        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1 . PFUser::SSH_KEY_SEPARATOR . $this->key2,
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => '',
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);
        self::assertCount(0, glob($this->gitolite_admin_dir . '/keydir/*.pub'));

        $this->assertEmptyGitStatus();
    }

    public function testItFlipsTheKeys(): void
    {
        $invalid_keys_collector = new InvalidKeysCollector();
        $this->git_exec->method('push')->willReturn(true);
        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1 . PFUser::SSH_KEY_SEPARATOR . $this->key2,
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);

        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key2 . PFUser::SSH_KEY_SEPARATOR . $this->key1,
        ]);
        $this->dumper->dumpSSHKeys($user, $invalid_keys_collector);
        self::assertStringEqualsFile($this->gitolite_admin_dir . '/keydir/john_do@0.pub', $this->key2);
        self::assertStringEqualsFile($this->gitolite_admin_dir . '/keydir/john_do@1.pub', $this->key1);

        $this->assertEmptyGitStatus();
    }

    public function testItDoesntGenerateAnyErrorsWhenThereAreNoChangesOnKeys(): void
    {
        $invalid_keys_collector = new InvalidKeysCollector();
        $this->git_exec->method('push')->willReturn(true);
        $user = new PFUser([
            'id'              => 12,
            'language_id'     => 'en',
            'user_name'       => 'john_do',
            'authorized_keys' => $this->key1 . PFUser::SSH_KEY_SEPARATOR . $this->key2,
        ]);
        self::assertTrue($this->dumper->dumpSSHKeys($user, $invalid_keys_collector));
        self::assertTrue($this->dumper->dumpSSHKeys($user, $invalid_keys_collector));
    }
}
