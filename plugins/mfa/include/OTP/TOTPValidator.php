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

class TOTPValidator
{
    const OUT_OF_SYNC_ACCEPTED_STEPS = 1;

    /**
     * @var TOTP
     */
    private $totp;

    public function __construct(TOTP $totp)
    {
        $this->totp = $totp;
    }

    /**
     * @return bool
     */
    public function validate($code, \DateTimeImmutable $current_time)
    {
        $is_valid = false;

        foreach ($this->getAcceptedStepTimes($current_time) as $time) {
            $is_valid |= hash_equals($this->totp->generateCode($time), $code);
        }

        return (bool) $is_valid;
    }

    private function getAcceptedStepTimes(\DateTimeImmutable $time)
    {
        yield $time;

        $time_step = $this->totp->getTOTPMode()->getTimeStep();
        for ($i = 1; $i <= self::OUT_OF_SYNC_ACCEPTED_STEPS; $i++) {
            $interval = new \DateInterval('PT' . $i * $time_step . 'S');

            yield $time->sub($interval);
            yield $time->add($interval);
        }
    }
}
