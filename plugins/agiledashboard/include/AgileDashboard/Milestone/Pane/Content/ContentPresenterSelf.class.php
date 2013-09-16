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

class AgileDashboard_Milestone_Pane_Content_ContentPresenterSelf extends AgileDashboard_Milestone_Pane_Content_ContentPresenter {

    /** @var Boolean */
    private $can_add_backlog_item_type;

    /** @var String */
    private $add_new_backlog_url;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done,
        $backlog_item_type,
        $can_add_backlog_item_type,
        $submit_url
    ) {
        parent::__construct($todo, $done, $backlog_item_type);
        $this->add_new_backlog_url       = $submit_url;
        $this->can_add_backlog_item_type = $can_add_backlog_item_type;
    }

    public function getTemplateName() {
        return 'pane-content-self-strategy';
    }

    public function can_prioritize() {
        return $this->can_add_backlog_item_type;
    }

    public function can_add_backlog_item() {
        return $this->can_add_backlog_item_type;
    }

    public function add_new_backlog_url() {
        return $this->add_new_backlog_url;
    }

    public function add_new_backlog_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_subitem', array($this->backlog_item_type));
     }

}

?>
