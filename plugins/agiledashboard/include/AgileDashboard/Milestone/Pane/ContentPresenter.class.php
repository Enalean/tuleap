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

class AgileDashboard_Milestone_Pane_ContentPresenter {
    /** @var AgileDashboard_Milestone_Pane_ContentRowPresenterCollection */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Pane_ContentRowPresenterCollection */
    private $done_collection;

    /** @var String */
    private $backlog_item_type;

    /** @var Boolean */
    private $can_add_backlog_item_type;

    public function __construct(
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $todo,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $done,
        $backlog_item_type,
        $can_add_backlog_item_type
    ) {
        $this->todo_collection           = $todo;
        $this->done_collection           = $done;
        $this->backlog_item_type         = $backlog_item_type;
        $this->can_add_backlog_item_type = $can_add_backlog_item_type;
    }

    public function setTodoCollection(AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $todo) {
        $this->todo_collection = $todo;
    }

    public function setDoneCollection(AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $done) {
        $this->done_collection = $done;
    }

    public function can_add_backlog_item() {
        return $this->can_add_backlog_item_type;
    }

    public function add_new_backlog_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_add_new', array($this->backlog_item_type));
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_title');
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function parent() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_parent');
    }

    public function todo_collection() {
        return $this->todo_collection;
    }

    public function done_collection() {
        return array();
    }
}

?>
