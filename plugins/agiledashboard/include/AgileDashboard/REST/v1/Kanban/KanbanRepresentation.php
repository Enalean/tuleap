<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\TrackerReference;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use TrackerFactory;
use PFUser;
use Exception;
use AgileDashboard_KanbanUserPreferences;
use AgileDashboard_KanbanActionsChecker;

class KanbanRepresentation
{

    public const ROUTE         = 'kanban';
    public const BACKLOG_ROUTE = 'backlog';
    public const ITEMS_ROUTE   = 'items';

    /**
     * @var int
     */
    public $id;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference
     */
    public $tracker;

    /**
     * @var int
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array {@type Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnRepresentation}
     */
    public $columns;

    /*
     * @var array
     */
    public $resources;

    /**
     * @var Tuleap\AgileDashboard\REST\v1\Kanban\KanbanBacklogInfoRepresentation
     */
    public $backlog;

    /**
     * @var Tuleap\AgileDashboard\REST\v1\Kanban\KanbanArchiveInfoRepresentation
     */
    public $archive;

    /**
     * @var bool {@type bool}
     */
    public $user_can_add_columns;

    /**
     * @var bool {@type bool}
     */
    public $user_can_reorder_columns;

    public function build(
        AgileDashboard_Kanban $kanban,
        AgileDashboard_KanbanColumnFactory $column_factory,
        AgileDashboard_KanbanUserPreferences $user_preferences,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
        $user_can_add_columns,
        $user_can_reorder_columns,
        $user_can_add_in_place,
        PFUser $user
    ) {
        $this->id                       = JsonCast::toInt($kanban->getId());
        $this->tracker_id               = JsonCast::toInt($kanban->getTrackerId());
        $this->uri                      = self::ROUTE . '/' . $this->id;
        $this->label                    = $kanban->getName();
        $this->columns                  = array();
        $this->user_can_add_columns     = $user_can_add_columns;
        $this->user_can_reorder_columns = $user_can_reorder_columns;

        $this->backlog = new KanbanBacklogInfoRepresentation();
        $this->backlog->build('Backlog', $user_preferences->isBacklogOpen($kanban, $user), $user_can_add_in_place);

        $this->archive = new KanbanArchiveInfoRepresentation();
        $this->archive->build('Archive', $user_preferences->isArchiveOpen($kanban, $user));

        $this->tracker = new TrackerReference();
        $this->tracker->build($this->getTracker($kanban));

        $this->setColumns($kanban, $column_factory, $kanban_actions_checker, $user_can_add_in_place, $user);

        $this->resources = array(
            'backlog' => array(
                'uri' => $this->uri . '/' . self::BACKLOG_ROUTE
            ),
            'items' => array(
                'uri' => $this->uri . '/' . self::ITEMS_ROUTE
            )
        );
    }

    private function setColumns(
        AgileDashboard_Kanban $kanban,
        AgileDashboard_KanbanColumnFactory $column_factory,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
        $user_can_add_in_place,
        PFUser $user
    ) {
        $columns = $column_factory->getAllKanbanColumnsForAKanban($kanban, $user);

        foreach ($columns as $column) {
            try {
                $kanban_actions_checker->checkUserCanDeleteColumn($user, $kanban, $column);
                $user_can_remove_column = true;
            } catch (Exception $exception) {
                $user_can_remove_column = false;
            }

            try {
                $kanban_actions_checker->checkUserCanEditColumnLabel($user, $kanban);
                $user_can_edit_label = true;
            } catch (Exception $exception) {
                $user_can_edit_label = false;
            }

            $column_representation = new KanbanColumnRepresentation();
            $column_representation->build($column, $user_can_add_in_place, $user_can_remove_column, $user_can_edit_label);

            $this->columns[] = $column_representation;
        }
    }

    private function getTracker(AgileDashboard_Kanban $kanban)
    {
        return TrackerFactory::instance()->getTrackerById($kanban->getTrackerId());
    }
}
