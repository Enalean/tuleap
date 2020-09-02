<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

class AutomaticUserRegistrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $username_generator;

    protected function setUp(): void
    {
        $this->username_generator = \Mockery::spy(\Tuleap\OpenIDConnectClient\Login\Registration\UsernameGenerator::class, ['getUsername' => 'foo']);
    }

    public function testItCreatesAnAccount(): void
    {
        $user_manager       = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('createAccount')->once();

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $automatic_user_registration->register(['email' => 'user@example.com']);
    }

    public function testItNeedsAnEmail(): void
    {
        $user_manager       = \Mockery::spy(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->register([]);
    }

    public function testItFillsLdapIdWithSpecifiedAttribute(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'email');

        $user_manager       = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('createAccount')->once()->andReturnArg(0);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $user = $automatic_user_registration->register(['email' => 'user@example.com']);

        self::assertInstanceOf(\PFUser::class, $user);
        self::assertSame('user@example.com', $user->getLdapId());
    }

    public function testItForbidsAccountCreationIfLdapAttributeIsNotInUserInfo(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = \Mockery::mock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->register(['email' => 'user@example.com']);
    }

    public function testCanCreateAccountDefaultsToEmail(): void
    {
        $user_manager = \Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('getAllUsersByEmail')->with('user@example.com')->once()->andReturn([]);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        self::assertTrue($automatic_user_registration->canCreateAccount(['email' => 'user@example.com']));
    }

    public function testCannotCreateAccountWhenNoEmail(): void
    {
        $user_manager = \Mockery::mock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->canCreateAccount([]);
    }

    public function testCanCreateAccountWithLdapId(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = \Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('getAllUsersByLdapID')->with('alice')->once()->andReturn([]);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        self::assertTrue($automatic_user_registration->canCreateAccount(['preferred_name' => 'alice']));
    }

    public function testCannotCreateAccountIfLdapAttributeIsNotExposedByUserInfo(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = \Mockery::mock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->canCreateAccount(['email' => 'user@example.com']);
    }
}
