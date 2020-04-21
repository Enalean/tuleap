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

class TOTPTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider RFCVectorsProvider
     */
    public function testRFCVectorsCode($shared_secret, $algorithm, $time, $expected_code)
    {
        $totp_mode = \Mockery::mock(TOTPMode::class);
        $totp_mode->shouldReceive('getTimeStep')->andReturns(30);
        $totp_mode->shouldReceive('getCodeLength')->andReturns(8);
        $totp_mode->shouldReceive('getAlgorithm')->andReturns($algorithm);
        $totp = new TOTP($totp_mode, $shared_secret);
        $code = $totp->generateCode(new \DateTimeImmutable('@' . $time));

        $this->assertEquals($expected_code, $code);
    }

    /**
     * @see https://tools.ietf.org/html/rfc6238#appendix-B
     * @see https://www.rfc-editor.org/errata_search.php?eid=2866
     */
    public function RFCVectorsProvider()
    {
        return [
            ['12345678901234567890', 'sha1', '59', '94287082'],
            ['12345678901234567890123456789012', 'sha256', '59', '46119246'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '59', '90693936'],
            ['12345678901234567890', 'sha1', '1111111109', '07081804'],
            ['12345678901234567890123456789012', 'sha256', '1111111109', '68084774'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '1111111109', '25091201'],
            ['12345678901234567890', 'sha1', '1111111111', '14050471'],
            ['12345678901234567890123456789012', 'sha256', '1111111111', '67062674'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '1111111111', '99943326'],
            ['12345678901234567890', 'sha1', '1234567890', '89005924'],
            ['12345678901234567890123456789012', 'sha256', '1234567890', '91819424'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '1234567890', '93441116'],
            ['12345678901234567890', 'sha1', '2000000000', '69279037'],
            ['12345678901234567890123456789012', 'sha256', '2000000000', '90698825'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '2000000000', '38618901'],
            ['12345678901234567890', 'sha1', '20000000000', '65353130'],
            ['12345678901234567890123456789012', 'sha256', '20000000000', '77737706'],
            ['1234567890123456789012345678901234567890123456789012345678901234', 'sha512', '20000000000', '47863826'],
        ];
    }
}
