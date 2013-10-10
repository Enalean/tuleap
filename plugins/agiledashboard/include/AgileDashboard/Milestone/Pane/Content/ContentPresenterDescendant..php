<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

class AgileDashboard_Milestone_Pane_Content_ContentPresenterDescendant extends AgileDashboard_Milestone_Pane_Content_ContentPresenter {
    /** @var Tracker[] */
    private $trackers = array();

    /** @var String[] */
    private $add_new_backlog_items_urls;

    /** @var Boolean */
    private $can_prioritize;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $inconsistent_collection,
        $backlog_item_type,
        $add_new_backlog_items_urls,
        $trackers,
        $can_prioritize,
        $trackers_without_initial_effort_defined
    ) {
        parent::__construct($todo, $done, $inconsistent_collection, $backlog_item_type, $trackers_without_initial_effort_defined);
        $this->add_new_backlog_items_urls  = $add_new_backlog_items_urls;
        $this->trackers                    = $trackers;
        $this->can_prioritize              = $can_prioritize;
    }

    public function getTemplateName() {
        return 'pane-content-descendant-strategy';
    }

    public function can_prioritize() {
        return $this->can_prioritize;
    }

    public function can_add_backlog_item() {
        return count($this->add_new_backlog_items_urls) > 0;
    }

    public function only_one_new_backlog_items_urls() {
        return count($this->add_new_backlog_items_urls) == 1;
    }

    public function add_new_backlog_items_urls() {
        return $this->add_new_backlog_items_urls;
    }

    public function trackers() {
        return $this->trackers;
    }

    public function create_new_specific_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_specific_item', $this->add_new_backlog_items_urls[0]['tracker_type']);
    }

    public function create_new_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_item');
    }

    public function create_new_item_help() {
        $trackers = array();
        foreach($this->add_new_backlog_items_urls as $backlog_entry) {
            array_push($trackers, $backlog_entry['tracker_type']);
        }
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_item_help', implode(', ', $trackers));
    }
}

?>
