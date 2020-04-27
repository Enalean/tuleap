<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;


class ExtractedTestCaseFromJunit
{
    /**
     * @var int
     */
    private $time;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $result;

    public function __construct(int $time, string $status, string $result)
    {
        $this->time   = $time;
        $this->status = $status;
        $this->result = $result;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function addTime(int $time): void
    {
        $this->time += $time;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function addFailureOnResult(string $failure): void
    {
        $this->result .= $failure;
    }
}
