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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServerFactory;
use Git_UserAccountManager;
use Git_UserSynchronisationException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserAccountManagerTest extends TestCase
{
    private PFUser $user;
    private Git_UserAccountManager $user_account_manager;
    private Git_Driver_Gerrit_UserAccountManager&MockObject $gerrit_user_account_manager;
    /** @var string[] */
    private array $original_keys;
    /** @var string[] */
    private array $new_keys;

    #[\Override]
    protected function setUp(): void
    {
        $this->user                        = new PFUser(['ldap_id' => 'testUser', 'language_id' => 'en']);
        $gerrit_driver_factory             = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $remote_gerrit_factory             = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_user_account_manager = $this->createMock(Git_Driver_Gerrit_UserAccountManager::class);

        $this->user_account_manager = new Git_UserAccountManager($gerrit_driver_factory, $remote_gerrit_factory);
        $this->user_account_manager->setGerritUserAccountManager($this->gerrit_user_account_manager);

        $this->original_keys = [
            'Im a key',
            'Im a key',
            'Im another key',
            'Im an identical key',
            'Im an additional key',
        ];

        $this->new_keys = [
            'Im a new key',
            'Im another new key',
            'Im another new key',
            'Im an identical key',
        ];
    }

    public function testItThrowsAnExceptionIfGerritSynchFails(): void
    {
        $this->gerrit_user_account_manager->expects($this->once())->method('synchroniseSSHKeys')->willThrowException(new Git_UserSynchronisationException());

        $this->expectException(Git_UserSynchronisationException::class);
        $this->user_account_manager->synchroniseSSHKeys(
            $this->original_keys,
            $this->new_keys,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfGerritPushFails(): void
    {
        $this->gerrit_user_account_manager->expects($this->once())->method('pushSSHKeys')->willThrowException(new Git_UserSynchronisationException());

        $this->expectException(Git_UserSynchronisationException::class);
        $this->user_account_manager->pushSSHKeys($this->user);
    }
}
