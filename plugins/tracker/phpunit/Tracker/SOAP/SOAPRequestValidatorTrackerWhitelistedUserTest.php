<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\SOAP;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\SOAP\SOAPRequestValidator;

class SOAPRequestValidatorTrackerWhitelistedUserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        \ForgeConfig::store();
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
    }

    /**
     * @expectedException \Tuleap\Tracker\SOAP\NotTrackerWhitelistedUserException
     */
    public function testAllUsersAreBlockedByDefault()
    {
        $request_validator           = \Mockery::mock(SOAPRequestValidator::class);
        $whitelist_request_validator = new SOAPRequestValidatorTrackerWhitelistedUser($request_validator);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user1');
        $request_validator->shouldReceive('continueSession')->andReturns($user);

        $allowed_user = $whitelist_request_validator->continueSession('session_key');
        $this->assertSame($user, $allowed_user);
    }

    public function testAWhitelistedUserCanAccessTheAPI()
    {
        $request_validator           = \Mockery::mock(SOAPRequestValidator::class);
        \ForgeConfig::set('soap_tracker_whitelisted_users', 'user3, user1,user2');
        $whitelist_request_validator = new SOAPRequestValidatorTrackerWhitelistedUser($request_validator);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user1');
        $request_validator->shouldReceive('continueSession')->andReturns($user);

        $allowed_user = $whitelist_request_validator->continueSession('session_key');
        $this->assertSame($user, $allowed_user);
    }

    /**
     * @expectedException \Tuleap\Tracker\SOAP\NotTrackerWhitelistedUserException
     */
    public function testANotWhitelistedUserCanNotAccessTheAPI()
    {
        $request_validator           = \Mockery::mock(SOAPRequestValidator::class);
        \ForgeConfig::set('soap_tracker_whitelisted_users', 'user2');
        $whitelist_request_validator = new SOAPRequestValidatorTrackerWhitelistedUser($request_validator);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user1');
        $request_validator->shouldReceive('continueSession')->andReturns($user);

        $whitelist_request_validator->continueSession('session_key');
    }
}
