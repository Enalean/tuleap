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

use ParagonIE\ConstantTime\Hex;

class TOTP
{
    public const TIME_ZERO = 0;

    /**
     * @var TOTPMode
     */
    private $totp_mode;
    /**
     * @var string
     */
    private $shared_secret;


    public function __construct(TOTPMode $totp_mode, $shared_secret)
    {
        $this->shared_secret = $shared_secret;
        $this->totp_mode     = $totp_mode;
    }

    /**
     * @see https://tools.ietf.org/html/rfc6238#section-4
     * @see https://tools.ietf.org/html/rfc4226#section-5
     * @return string
     */
    public function generateCode(\DateTimeImmutable $time)
    {
        $counter_value = $this->getCounterValue($time);

        $hash = hash_hmac($this->totp_mode->getAlgorithm(), $counter_value, $this->shared_secret, true);

        $hash_length = mb_strlen($hash, '8bit');

        $offset = unpack('C', $hash[$hash_length - 1]);
        $offset = $offset[1] & 0x0f;

        $hash_truncated = array_values(
            unpack('C*', mb_substr($hash, $offset, 4, '8bit'))
        );

        $binary = (
            (($hash_truncated[0] & 0x7f) << 24) |
            (($hash_truncated[1] & 0xff) << 16) |
            (($hash_truncated[2] & 0xff) << 8) |
             ($hash_truncated[3] & 0xff)
        );

        $result = $binary % (10 ** $this->totp_mode->getCodeLength());

        return str_pad((string) $result, $this->totp_mode->getCodeLength(), '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    private function getCounterValue(\DateTimeImmutable $time)
    {
        $value = intdiv($time->getTimestamp() - self::TIME_ZERO, $this->totp_mode->getTimeStep());

        $hexadecimal_value = str_pad(dechex($value), 16, '0', STR_PAD_LEFT);

        return Hex::decode($hexadecimal_value);
    }

    /**
     * @return TOTPMode
     */
    public function getTOTPMode()
    {
        return $this->totp_mode;
    }
}
