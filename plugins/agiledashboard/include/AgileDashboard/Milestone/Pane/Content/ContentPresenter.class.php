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

class AgileDashboard_Milestone_Pane_Content_ContentPresenter {
    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection */
    private $done_collection;

    /** @var String */
    private $backlog_item_type;

    /** @var Boolean */
    private $can_add_backlog_item_type;

    /** @var String */
    private $submit_url;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done,
        $parent_item_type,
        $backlog_item_type,
        $can_add_backlog_item_type,
        $submit_url
    ) {
        $this->todo_collection           = $todo;
        $this->done_collection           = $done;
        $this->parent_item_type          = $parent_item_type;
        $this->backlog_item_type         = $backlog_item_type;
        $this->can_add_backlog_item_type = $can_add_backlog_item_type;
        $this->submit_url                = $submit_url;
    }

    public function setTodoCollection(AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $todo) {
        $this->todo_collection = $todo;
    }

    public function setDoneCollection(AgileDashboard_Milestone_Backlog_BacklogRowPresenterCollection $done) {
        $this->done_collection = $done;
    }

    public function backlog_item_type() {
        return $this->backlog_item_type;
    }

    public function can_add_backlog_item() {
        return $this->can_add_backlog_item_type;
    }

    public function can_prioritize() {
        return $this->can_add_backlog_item_type;
    }

    public function add_new_backlog_url() {
        return $this->submit_url;
    }

    public function add_new_backlog_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'add_subitem', array($this->backlog_item_type));
    }

    public function title() {
        return $this->backlog_item_type;
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function parent() {
        if ($this->parent_item_type) {
            return $this->parent_item_type;
        } else {
            return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_parent');
        }
    }

    public function todo_collection() {
        return $this->todo_collection;
    }

    public function done_collection() {
        return $this->done_collection;
    }

    public function has_something_todo() {
        return $this->todo_collection->count() > 0;
    }

    public function has_something_done() {
        return $this->done_collection->count() > 0;
    }

    public function has_something() {
        return $this->has_something_todo() || $this->has_something_done();
    }

    public function closed_items_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_title', $this->backlog_item_type);
    }

    public function closed_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_intro', $this->backlog_item_type);
    }

    public function closed_items_nothing_yet() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_nothing_yet');
    }

    public function open_items_title() {
        $key = 'open_items_title';
        if (! $this->has_something()) {
            $key = 'open_items_title-not_yet';
            if ($this->can_add_backlog_item()) {
                $key = 'open_items_title-not_yet-can_add';
            }
        } else if (! $this->has_something_todo()) {
            $key = 'open_items_title-no_more';
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', $key, $this->backlog_item_type);
    }

    public function open_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'open_items_intro', $this->backlog_item_type);
    }

    public function lab() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'lab');
    }
}

?>
