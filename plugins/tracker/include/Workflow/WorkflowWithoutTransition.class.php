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

/**
 * A Workflow without transition.
 *
 * This is typically the case when there is no workflow defined(aka no transition)
 * for a given tracker.
 */
class WorkflowWithoutTransition extends Workflow // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function __construct(
        Tracker_RulesManager $global_rules_manager,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        WorkflowBackendLogger $logger,
        $tracker_id
    ) {
        $workflow_id = 0;
        $field_id    = 0;
        $is_used     = false;
        $is_advanced = true;
        $is_legacy   = false;
        parent::__construct(
            $global_rules_manager,
            $trigger_rules_manager,
            $logger,
            $workflow_id,
            $tracker_id,
            $field_id,
            $is_used,
            $is_advanced,
            $is_legacy
        );
    }
}
