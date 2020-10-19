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

use Tuleap\Tracker\Artifact\Artifact;

class ExecutionWithAutomatedTestData
{
    /**
     * @var string
     */
    private $automated_test;
    /**
     * @var Artifact
     */
    private $execution;

    public function __construct(
        Artifact $execution,
        string $automated_test
    ) {
        $this->execution      = $execution;
        $this->automated_test = $automated_test;
    }

    public function getExecution(): Artifact
    {
        return $this->execution;
    }

    public function getAutomatedTest(): string
    {
        return $this->automated_test;
    }
}
