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

class TOTPMode
{
    const SUPPORTED_ALGORITHMS = ['sha1', 'sha256', 'sha512'];

    /**
     * @var int
     */
    private $time_step;
    /**
     * @var int
     */
    private $code_length;
    /**
     * @var string
     */
    private $algorithm;

    public function __construct($time_step, $code_length, $algorithm)
    {
        if (! is_int($time_step)) {
            throw new \TypeError('Expected $time_step to be an int, got ' . gettype($time_step));
        }
        if ($time_step < 1) {
            throw new \InvalidArgumentException('$time_step must be a positive integer');
        }
        $this->time_step = $time_step;

        if (! is_int($code_length)) {
            throw new \TypeError('Expected $code_length to be an int, got ' . gettype($code_length));
        }
        if ($code_length < 6 || $code_length > 8) {
            throw new \InvalidArgumentException('$code_length must be between 6 and 8');
        }
        $this->code_length = $code_length;

        if (! in_array($algorithm, self::SUPPORTED_ALGORITHMS, true)) {
            throw new \InvalidArgumentException('$algorithm must be either ' . implode(', ', self::SUPPORTED_ALGORITHMS));
        }
        $this->algorithm = $algorithm;
    }

    /**
     * @return int
     */
    public function getTimeStep()
    {
        return $this->time_step;
    }

    /**
     * @return int
     */
    public function getCodeLength()
    {
        return $this->code_length;
    }

    /**
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }
}
