<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once __DIR__.'/../../bootstrap.php';

class Tracker_Report_Test extends TuleapTestCase {

    public function itExportsToSoap() {
        $report_id           = 1;
        $name                = 'the report';
        $description         = 'the description';
        $current_renderer_id = null;
        $parent_report_id    = null;
        $user_id             = null;
        $is_default          = true;
        $tracker_id          = 115;
        $is_query_displayed  = true;
        $is_in_expert_mode   = false;
        $expert_query        = '';
        $updated_by          = null;
        $updated_at          = null;
        $report  = new Tracker_Report(
            $report_id,
            $name,
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

        $this->assertEqual($report->exportToSoap(), array(
           'id' => $report_id,
           'name' => $name,
           'description' => $description,
           'user_id' => $user_id,
           'is_default' => $is_default
        ));
    }
}

?>
