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
 * NullObject pattern for the Workflow. This is typically the case when there is
 * no workflow defined(aka no transition) for a given tracker.
 */
class NullWorkflow extends Workflow {

    public function __construct($tracker_id) {
        $workflow_id = 0;
        $field_id    = 0;
        $is_used     = false;
        parent::__construct($workflow_id, $tracker_id, $field_id, $is_used);
    }
}
