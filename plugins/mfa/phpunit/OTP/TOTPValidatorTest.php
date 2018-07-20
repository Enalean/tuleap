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

namespace Tuleap\MFA\OTP;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TOTPValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testValidation()
    {
        $totp_success           = \Mockery::mock(TOTP::class);
        $totp_validator_success = new TOTPValidator();
        $totp_failure           = \Mockery::mock(TOTP::class);
        $totp_validator_failure = new TOTPValidator();
        $current_time           = \Mockery::mock(\DateTimeImmutable::class);
        $totp_mode              = \Mockery::mock(TOTPMode::class);

        $totp_success->shouldReceive('generateCode')->andReturn('0000000', '111111', '222222');
        $totp_success->shouldReceive('getTOTPMode')->andReturns($totp_mode);
        $totp_failure->shouldReceive('generateCode')->andReturn('777777', '888888', '999999');
        $totp_failure->shouldReceive('getTOTPMode')->andReturns($totp_mode);
        $totp_mode->shouldReceive('getTimeStep')->andReturns(30);
        $current_time->shouldReceive('sub')->andReturns(\Mockery::mock(\DateTimeImmutable::class));
        $current_time->shouldReceive('add')->andReturns(\Mockery::mock(\DateTimeImmutable::class));

        $this->assertTrue($totp_validator_success->validate($totp_success, '111111', $current_time));
        $this->assertFalse($totp_validator_failure->validate($totp_failure, '111111', $current_time));
    }
}
