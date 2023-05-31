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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

final class AutomaticUserRegistrationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private UsernameGenerator&\PHPUnit\Framework\MockObject\MockObject $username_generator;

    protected function setUp(): void
    {
        $this->username_generator = $this->createMock(\Tuleap\OpenIDConnectClient\Login\Registration\UsernameGenerator::class);
        $this->username_generator->method('getUsername')->willReturn('jdoe');
    }

    public function testItCreatesAnAccount(): void
    {
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->expects(self::once())->method('createAccount');

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $automatic_user_registration->register(['email' => 'user@example.com']);
    }

    public function testItNeedsAnEmail(): void
    {
        $user_manager = $this->createMock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->register([]);
    }

    public function testItFillsLdapIdWithSpecifiedAttribute(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'email');

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->expects(self::once())->method('createAccount')->willReturnCallback(static fn (\PFUser $user) => $user);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $user                        = $automatic_user_registration->register(['email' => 'user@example.com']);

        self::assertInstanceOf(\PFUser::class, $user);
        self::assertSame('user@example.com', $user->getLdapId());
    }

    public function testItForbidsAccountCreationIfLdapAttributeIsNotInUserInfo(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = $this->createMock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->register(['email' => 'user@example.com']);
    }

    public function testCanCreateAccountDefaultsToEmail(): void
    {
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->expects(self::once())->method('getAllUsersByEmail')->with('user@example.com')->willReturn([]);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        self::assertTrue($automatic_user_registration->canCreateAccount(['email' => 'user@example.com']));
    }

    public function testCannotCreateAccountWhenNoEmail(): void
    {
        $user_manager = $this->createMock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->canCreateAccount([]);
    }

    public function testCanCreateAccountWithLdapId(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->expects(self::once())->method('getAllUsersByLdapID')->with('alice')->willReturn([]);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        self::assertTrue($automatic_user_registration->canCreateAccount(['preferred_name' => 'alice']));
    }

    public function testCannotCreateAccountIfLdapAttributeIsNotExposedByUserInfo(): void
    {
        \ForgeConfig::set(AutomaticUserRegistration::CONFIG_LDAP_ATTRIBUTE, 'preferred_name');

        $user_manager = $this->createMock(\UserManager::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $this->username_generator);
        $this->expectException(\Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException::class);
        $automatic_user_registration->canCreateAccount(['email' => 'user@example.com']);
    }
}
