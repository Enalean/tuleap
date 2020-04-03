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
use Tuleap\GlobalLanguageMock;

require_once(__DIR__ . '/../../bootstrap.php');

class AutomaticUserRegistrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testItCreatesAnAccount(): void
    {
        $user_manager       = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('createAccount')->once();
        $username_generator = \Mockery::spy(\Tuleap\OpenIDConnectClient\Login\Registration\UsernameGenerator::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $username_generator);
        $automatic_user_registration->register(array('email' => 'user@example.com'));
    }

    public function testItNeedsAnEmail(): void
    {
        $user_manager       = \Mockery::spy(\UserManager::class);
        $username_generator = \Mockery::spy(\Tuleap\OpenIDConnectClient\Login\Registration\UsernameGenerator::class);

        $automatic_user_registration = new AutomaticUserRegistration($user_manager, $username_generator);
        $this->expectException('Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToRegisterUserException');
        $automatic_user_registration->register(array());
    }
}
