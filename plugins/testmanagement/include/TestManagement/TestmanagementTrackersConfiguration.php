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

namespace Tuleap\TestManagement;

class TestmanagementTrackersConfiguration
{
    /** @var TestmanagementConfigTracker */
    private $campaign;
    /** @var TestmanagementConfigTracker */
    private $test_definition;
    /** @var TestmanagementConfigTracker */
    private $test_execution;
    /** @var TestmanagementConfigTracker | null */
    private $issue;

    public function setCampaign(TestmanagementConfigTracker $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function setTestDefinition(TestmanagementConfigTracker $test_definition): void
    {
        $this->test_definition = $test_definition;
    }

    public function setTestExecution(TestmanagementConfigTracker $test_execution): void
    {
        $this->test_execution = $test_execution;
    }

    public function setIssue(TestmanagementConfigTracker $issue): void
    {
        $this->issue = $issue;
    }

    public function getCampaign(): TestmanagementConfigTracker
    {
        return $this->campaign;
    }

    public function getTestDefinition(): TestmanagementConfigTracker
    {
        return $this->test_definition;
    }

    public function getTestExecution(): TestmanagementConfigTracker
    {
        return $this->test_execution;
    }

    public function getIssue(): ?TestmanagementConfigTracker
    {
        return $this->issue;
    }
}
