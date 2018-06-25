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

use PHPUnit\Framework\TestCase;

class TOTPModeTest extends TestCase
{
    public function testInstantiateTOTPMode()
    {
        $time_step   = 30;
        $code_length = 6;
        $algorithm   = 'sha1';

        $totp_mode = new TOTPMode($time_step, $code_length, $algorithm);

        $this->assertEquals($time_step, $totp_mode->getTimeStep());
        $this->assertEquals($code_length, $totp_mode->getCodeLength());
        $this->assertEquals($algorithm, $totp_mode->getAlgorithm());
    }

    /**
     * @expectedException \TypeError
     * @expectedExceptionMessageRegExp *time_step*
     */
    public function testExpectsTimeStepToBeAnInt()
    {
        new TOTPMode('Time step', 6, 'sha1');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExpectsTimeStepToBePositive()
    {
        new TOTPMode(-1, 6, 'sha1');
    }

    /**
     * @expectedException \TypeError
     * @expectedExceptionMessageRegExp *code_length*
     */
    public function testExpectsCodeLengthToBeAnInt()
    {
        new TOTPMode(30, 'code_length', 'sha1');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExpectsCodeLengthToBeInAnAcceptableRange()
    {
        new TOTPMode(30, 99999999999999, 'sha1');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExpectsASupportedAlgorithm()
    {
        new TOTPMode(30, 6, 'sha3');
    }
}
