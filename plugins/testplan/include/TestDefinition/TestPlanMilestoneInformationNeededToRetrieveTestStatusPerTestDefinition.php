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

/**
 * @psalm-immutable
 */
final class TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition
{
    /**
     * @var int
     */
    public $milestone_id;
    /**
     * @var int[]
     * @psalm-var non-empty-array<int>
     */
    public $test_definition_ids;
    /**
     * @var array<string|int>
     * @psalm-var non-empty-array<string|int>
     */
    public $current_user_ugroup_ids;
    /**
     * @var int
     */
    public $test_exec_status_field_id;
    /**
     * @var int
     */
    public $test_exec_art_link_field_id;
    /**
     * @var int
     */
    public $test_campaign_art_link_field_id;

    /**
     * @param \Tuleap\Tracker\Artifact\Artifact[]                      $test_definitions
     *
     * @psalm-param non-empty-array<\Tuleap\Tracker\Artifact\Artifact> $test_definitions
     *
     * @param array<string|int>                                        $current_user_ugroup_ids
     *
     * @psalm-param non-empty-array<string|int>                        $current_user_ugroup_ids
     */
    public function __construct(
        \Tuleap\Tracker\Artifact\Artifact $milestone,
        array $test_definitions,
        array $current_user_ugroup_ids,
        int $test_exec_status_field_id,
        int $test_exec_art_link_field_id,
        int $test_campaign_art_link_field_id
    ) {
        $this->milestone_id  = (int) $milestone->getId();
        $test_definition_ids = [];
        foreach ($test_definitions as $test_definition) {
            $test_definition_ids[] = (int) $test_definition->getId();
        }
        $this->test_definition_ids             = $test_definition_ids;
        $this->current_user_ugroup_ids         = $current_user_ugroup_ids;
        $this->test_exec_status_field_id       = $test_exec_status_field_id;
        $this->test_exec_art_link_field_id     = $test_exec_art_link_field_id;
        $this->test_campaign_art_link_field_id = $test_campaign_art_link_field_id;
    }
}
