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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class UserAccountManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $user;
    private $gerrit_driver_factory;
    private $remote_gerrit_factory;

    /**
     * @var Git_UserAccountManager
     */
    private $user_account_manager;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_user_account_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                         = new PFUser(['ldap_id' => 'testUser', 'language_id' => 'en']);
        $this->gerrit_driver_factory        = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->remote_gerrit_factory        = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_user_account_manager  = \Mockery::spy(\Git_Driver_Gerrit_UserAccountManager::class);

        $this->user_account_manager = new Git_UserAccountManager(
            $this->gerrit_driver_factory,
            $this->remote_gerrit_factory
        );
        $this->user_account_manager->setGerritUserAccountManager($this->gerrit_user_account_manager);

        $this->original_keys = array(
            'Im a key',
            'Im a key',
            'Im another key',
            'Im an identical key',
            'Im an additional key',
        );

        $this->new_keys = array(
            'Im a new key',
            'Im another new key',
            'Im another new key',
            'Im an identical key',
        );
    }

    public function testItThrowsAnExceptionIfGerritSynchFails(): void
    {
        $this->gerrit_user_account_manager->shouldReceive('synchroniseSSHKeys')->once()->andThrows(new Git_UserSynchronisationException());

        $this->expectException(\Git_UserSynchronisationException::class);

        $this->user_account_manager->synchroniseSSHKeys(
            $this->original_keys,
            $this->new_keys,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfGerritPushFails(): void
    {
        $this->gerrit_user_account_manager->shouldReceive('pushSSHKeys')->once()->andThrows(new Git_UserSynchronisationException());

        $this->expectException(\Git_UserSynchronisationException::class);

        $this->user_account_manager->pushSSHKeys(
            $this->user
        );
    }
}
