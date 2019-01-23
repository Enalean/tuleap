<?php
/**
 * Copyright (c) Enalean, 2013 - 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tuleap\REST\JsonCast;
use Workflow;

class WorkflowRepresentation
{

    /**
     * @var int
     */
    public $field_id;

    /**
     * @var bool
     */
    public $is_used;

    /**
     * @var bool
     */
    public $is_legacy;

    /**
     * @var \Tuleap\Tracker\REST\WorkflowRulesRepresentation
     */
    public $rules;

    /**
     * @var \Tuleap\Tracker\REST\WorkflowTransitionRepresentation[]
     */
    public $transitions = array();

    public function build(Workflow $workflow, WorkflowRulesRepresentation $rules, array $transitions)
    {
        $this->field_id    = JsonCast::toInt($workflow->getFieldId());
        $this->is_used     = $workflow->is_used;
        $this->is_legacy   = JsonCast::toBoolean($workflow->isLegacy());
        $this->rules       = $rules;
        $this->transitions = $transitions;
    }
}
