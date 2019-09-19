<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html

require_once __DIR__.'/../bootstrap.php';
function aTrackerReport()
{
    return new Test_TrackerReport_Builder();
}

class Test_TrackerReport_Builder
{
    private $id;
    private $tracker;
    private $name;

    public function __construct()
    {
        $this->id = uniqid();
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function withName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return \Tracker_Report
     */
    public function build()
    {
        $tracker_id = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $is_query_displayed = $is_in_expert_mode = $expert_query = $updated_by = $updated_at = null;
        $report = new Tracker_Report(
            $this->id,
            $this->name,
            $description,
            $current_renderer_id,
            $parent_report_id,
            $user_id,
            $is_default,
            $tracker_id,
            $is_query_displayed,
            $is_in_expert_mode,
            $expert_query,
            $updated_by,
            $updated_at
        );
        if ($this->tracker) {
            $report->setTracker($this->tracker);
        }
        return $report;
    }
}
