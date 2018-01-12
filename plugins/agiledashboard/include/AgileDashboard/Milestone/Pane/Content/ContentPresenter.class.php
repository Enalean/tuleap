<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
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

class AgileDashboard_Milestone_Pane_Content_ContentPresenter
{
    public $has_burndown;
    public $burndown_label;
    public $burndown_url;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $done_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $inconsistent_collection;

    /** @var String */
    private $backlog_item_type;

    /** @var String[] */
    private $trackers_without_initial_effort_field;

    /** @var Boolean */
    private $can_prioritize;

    /** @var String */
    private $solve_inconsistencies_url;

    public function __construct(
        Planning_Milestone $milestone,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $done,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $inconsistent_collection,
        $trackers,
        $can_prioritize,
        array $trackers_without_initial_effort_defined,
        $solve_inconsistencies_url,
        PFUser $user
    ) {
        $this->todo_collection           = $todo;
        $this->done_collection           = $done;
        $this->inconsistent_collection   = $inconsistent_collection;
        $this->backlog_item_type         = $this->getTrackerNames($trackers);
        foreach ($trackers_without_initial_effort_defined as $tracker) {
            $this->trackers_without_initial_effort_field[] = $tracker->getName();
        }
        $this->can_prioritize              = $can_prioritize;
        $this->solve_inconsistencies_url   = $solve_inconsistencies_url;

        $this->setBurndownAttributes($milestone, $user);
    }

    private function getTrackerNames($trackers)
    {
        $tracker_names = array();

        foreach ($trackers as $tracker) {
            $tracker_names[] = $tracker->getName();
        }

        return implode(', ', $tracker_names);
    }

    public function getTemplateName() {
        return 'pane-content';
    }

    public function can_prioritize() {
        return $this->can_prioritize;
    }

    public function solve_inconsistencies_button() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'solve_inconsistencies');
    }

    public function solve_inconsistencies_url() {
        return $this->solve_inconsistencies_url;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_title');
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function type() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_type');
    }

    public function parent() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_parent');
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

    public function has_nothing() {
        return ! $this->has_something();
    }

    public function has_nothing_todo() {
        return ! $this->has_something_todo();
    }

    public function closed_items_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_title', $this->backlog_item_type);
    }

    public function closed_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_intro', $this->backlog_item_type);
    }

    public function open_items_title() {
        $key = 'open_items_title';
        if ($this->has_nothing()) {
            $key = 'open_items_title-not_yet';
        } else if ($this->has_nothing_todo()) {
            $key = 'open_items_title-no_more';
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', $key, $this->backlog_item_type);
    }

    public function open_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'open_items_intro', $this->backlog_item_type);
    }

    public function user_cannot_prioritize() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'unsufficient_rights_for_ranking');
    }

    public function initial_effort_not_defined() {
        return count($this->trackers_without_initial_effort_field) > 0;
    }

    public function initial_effort_warning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'initial_effort_warning', implode(', ', $this->trackers_without_initial_effort_field));
    }

    public function inconsistent_items_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'inconsistent_items_title', $this->backlog_item_type);
    }

    public function inconsistent_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'inconsistent_items_intro');
    }

    public function has_something_inconsistent() {
        return count($this->inconsistent_collection) > 0;
    }

    public function inconsistent_collection() {
        return $this->inconsistent_collection;
    }

    private function setBurndownAttributes(Planning_Milestone $milestone, PFUser $user)
    {
        $this->has_burndown = false;

        $artifact       = $milestone->getArtifact();
        $burndown_field = $artifact->getABurndownField($user);
        if (! $burndown_field) {
            return;
        }

        $this->has_burndown   = true;
        $this->burndown_label = $burndown_field->getLabel();
        $this->burndown_url   = $burndown_field->getBurndownImageUrl($artifact);
    }
}
