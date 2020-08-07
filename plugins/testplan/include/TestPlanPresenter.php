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

declare(strict_types=1);

namespace Tuleap\TestPlan;

use AgileDashboard_MilestonePresenter;

class TestPlanPresenter
{
    /**
     * @var AgileDashboard_MilestonePresenter
     *
     * @psalm-readonly
     */
    public $milestone_presenter;
    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $user_display_name;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $milestone_id;
    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $milestone_title;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $project_id;
    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $project_name;
    /**
     * @var bool
     *
     * @psalm-readonly
     */
    public $user_can_create_campaign;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $test_definition_tracker_id = 0;
    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $test_definition_tracker_name = '';
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $expand_backlog_item_id;
    /**
     * @var int
     *
     * @psalm-readonly
     */
    public $highlight_test_definition_id;

    public function __construct(
        AgileDashboard_MilestonePresenter $milestone_presenter,
        string $user_display_name,
        int $milestone_id,
        string $milestone_title,
        int $project_id,
        string $project_name,
        bool $user_can_create_campaign,
        ?\Tracker $test_definition_tracker,
        int $expand_backlog_item_id,
        int $highlight_test_definition_id
    ) {
        $this->milestone_presenter = $milestone_presenter;
        $this->user_display_name   = $user_display_name;
        $this->milestone_id        = $milestone_id;
        $this->milestone_title              = $milestone_title;
        $this->project_id                   = $project_id;
        $this->project_name                 = $project_name;
        $this->user_can_create_campaign     = $user_can_create_campaign;
        $this->expand_backlog_item_id       = $expand_backlog_item_id;
        $this->highlight_test_definition_id = $highlight_test_definition_id;
        if ($test_definition_tracker !== null) {
            $this->test_definition_tracker_id   = $test_definition_tracker->getId();
            $this->test_definition_tracker_name = $test_definition_tracker->getName();
        }
    }
}
