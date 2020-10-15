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

use PFUser;

final class TestPlanTestDefinitionWithTestStatus
{
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $test_definition;
    /**
     * @var string|null
     * @psalm-var null|"notrun"|"passed"|"failed"|"blocked"
     */
    private $status;

    /**
     * @var int|null
     */
    private $test_execution_id_used_to_define_status;
    /**
     * @var int|null
     */
    private $test_execution_date;
    /**
     * @var PFUser|null
     */
    private $test_execution_submitted_by;
    /**
     * @var int|null
     */
    private $test_campaign_id;

    /**
     * @psalm-param null|"notrun"|"passed"|"failed"|"blocked" $status
     */
    private function __construct(
        \Tuleap\Tracker\Artifact\Artifact $test_definition,
        ?string $status,
        ?int $test_execution_id_used_to_define_status,
        ?int $test_execution_date,
        ?PFUser $test_execution_submitted_by,
        ?int $test_campaign_id
    ) {
        $this->test_definition                         = $test_definition;
        $this->status                                  = $status;
        $this->test_execution_id_used_to_define_status = $test_execution_id_used_to_define_status;
        $this->test_execution_date                     = $test_execution_date;
        $this->test_execution_submitted_by             = $test_execution_submitted_by;
        $this->test_campaign_id                        = $test_campaign_id;
    }

    public static function unknownTestStatusForTheDefinition(\Tuleap\Tracker\Artifact\Artifact $test_definition): self
    {
        return new self($test_definition, null, null, null, null, null);
    }

    /**
     * @psalm-param "notrun"|"passed"|"failed"|"blocked" $status
     */
    public static function knownTestStatusForTheDefinition(
        \Tuleap\Tracker\Artifact\Artifact $test_definition,
        string $status,
        int $test_execution_id_used_to_define_status,
        int $test_execution_date,
        PFUser $test_execution_submitted_by,
        int $test_campaign_id
    ): self {
        return new self(
            $test_definition,
            $status,
            $test_execution_id_used_to_define_status,
            $test_execution_date,
            $test_execution_submitted_by,
            $test_campaign_id
        );
    }

    public function getTestDefinition(): \Tuleap\Tracker\Artifact\Artifact
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

    public function getTestExecutionIdUsedToDefineStatus(): ?int
    {
        return $this->test_execution_id_used_to_define_status;
    }

    public function getTestExecutionDate(): ?int
    {
        return $this->test_execution_date;
    }

    public function getTestExecutionSubmittedBy(): ?PFUser
    {
        return $this->test_execution_submitted_by;
    }

    public function getTestCampaignIdDefiningTheStatus(): ?int
    {
        return $this->test_campaign_id;
    }
}
