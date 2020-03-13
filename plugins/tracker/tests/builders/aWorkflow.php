<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

require_once __DIR__ . '/../bootstrap.php';

function aWorkflow()
{
    return new Test_Workflow_Builder();
}

class Test_Workflow_Builder
{
    private $id          = 1;
    private $tracker_id  = 2;
    private $field_id    = 3;
    private $is_used     = 1;
    private $transitions = null;
    private $global_rules_manager;
    private $trigger_rules_manager;

    public function __construct()
    {
        $this->global_rules_manager = mock('Tracker_RulesManager');
        $this->trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
    }

    public function withGlobalRulesManager(Tracker_RulesManager $global_rules_manager)
    {
        $this->global_rules_manager  = $global_rules_manager;
        return $this;
    }

    public function withTriggerRulesManager(Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager)
    {
        $this->trigger_rules_manager = $trigger_rules_manager;
        return $this;
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withFieldId($field_id)
    {
        $this->field_id = $field_id;
        return $this;
    }

    public function withTrackerId($tracker_id)
    {
        $this->tracker_id = $tracker_id;
        return $this;
    }

    public function withIsUsed($is_used)
    {
        $this->is_used = $is_used;
        return $this;
    }

    public function withTransitions($transitions)
    {
        $this->transitions = $transitions;
        return $this;
    }

    public function build()
    {
        return new Workflow(
            $this->global_rules_manager,
            $this->trigger_rules_manager,
            new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG),
            $this->id,
            $this->tracker_id,
            $this->field_id,
            $this->is_used,
            true,
            false,
            $this->transitions
        );
    }
}
