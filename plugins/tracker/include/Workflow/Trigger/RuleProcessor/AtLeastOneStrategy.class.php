<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * This strategy might sounds strange but:
 * - We assume that rule processor is only called when a rule can be applied
 *   that means that at least one child (the one modified) satify the pre condition
 *   therefore, the rule can be applied.
 */
class Tracker_Workflow_Trigger_RulesProcessor_AtLeastOneStrategy implements Tracker_Workflow_Trigger_RulesProcessor_Strategy
{

    /**
     * @see Tracker_Workflow_Trigger_RulesProcessor_Strategy::allPrecondtionsAreMet
     * @return bool
     */
    public function allPrecondtionsAreMet()
    {
        return true;
    }
}
