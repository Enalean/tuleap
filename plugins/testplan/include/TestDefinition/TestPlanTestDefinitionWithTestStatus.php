<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestPlan\TestDefinition;

final class TestPlanTestDefinitionWithTestStatus
{
    /**
     * @var \Tracker_Artifact
     */
    private $test_definition;
    /**
     * @var string|null
     * @psalm-var null|"notrun"|"passed"|"failed"|"blocked"
     */
    private $status;

    /**
     * @psalm-param null|"notrun"|"passed"|"failed"|"blocked" $status
     */
    private function __construct(\Tracker_Artifact $test_definition, ?string $status)
    {
        $this->test_definition = $test_definition;
        $this->status          = $status;
    }

    public static function unknownTestStatusForTheDefinition(\Tracker_Artifact $test_definition): self
    {
        return new self($test_definition, null);
    }

    /**
     * @psalm-param "notrun"|"passed"|"failed"|"blocked" $status
     */
    public static function knownTestStatusForTheDefinition(\Tracker_Artifact $test_definition, string $status): self
    {
        return new self($test_definition, $status);
    }

    public function getTestDefinition(): \Tracker_Artifact
    {
        return $this->test_definition;
    }

    /**
     * @psalm-return null|"notrun"|"passed"|"failed"|"blocked"
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }
}
