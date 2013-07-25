<?php

/*
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Tracker_CrossSearch_SearchContentViewEmpty extends Tracker_CrossSearch_SearchContentView {

    public function __construct(Tracker_Report                   $report,
                                array                            $criteria,
                                PFUser $user) {
        $this->report = $report;
        $this->user   = $user;
        $this->criteria = $criteria;
        $this->tree_of_artifacts = new TreeNode();
    }

    protected function fetchResults() {
        $html  = '';
        $html .= '<div class="tracker_report_renderer">';
        $html .= '<em>'.$GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'please_select_criterion').'<em>';
        $html .= '</div>';

        return $html;
    }
}

?>
