<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tracker;
use Workflow_Dao;

class ModeUpdater
{
    /**
     * @var Workflow_Dao
     */
    private $workflow_dao;

    public function __construct(Workflow_Dao $workflow_dao)
    {
        $this->workflow_dao = $workflow_dao;
    }

    public function switchWorkflowToAdvancedMode(Tracker $tracker)
    {
        $workflow_id = $tracker->getWorkflow()->getId();

        $this->workflow_dao->switchWorkflowToAdvancedMode($workflow_id);
    }
}
