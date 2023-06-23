<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanActionsChecker;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanUserPreferences;
use Exception;
use PFUser;
use TrackerFactory;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class KanbanRepresentation
{
    public const ROUTE         = 'kanban';
    public const BACKLOG_ROUTE = 'backlog';
    public const ITEMS_ROUTE   = 'items';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $tracker_id;

    /**
     * @var KanbanTrackerRepresentation
     */
    public $tracker;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array {@type Tuleap\Kanban\REST\v1\KanbanColumnRepresentation}
     */
    public $columns;

    /**
     * @var array
     */
    public $resources;

    /**
     * @var KanbanBacklogInfoRepresentation
     */
    public $backlog;

    /**
     * @var KanbanArchiveInfoRepresentation
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

    /**
     * @var bool {@type bool}
     */
    public $user_can_add_artifact;

    private function __construct(
        int $id,
        int $tracker_id,
        KanbanTrackerRepresentation $tracker,
        string $uri,
        string $label,
        array $columns,
        array $resources,
        KanbanBacklogInfoRepresentation $backlog,
        KanbanArchiveInfoRepresentation $archive,
        bool $user_can_add_columns,
        bool $user_can_reorder_columns,
        bool $user_can_add_artifact,
    ) {
        $this->id                       = $id;
        $this->tracker_id               = $tracker_id;
        $this->tracker                  = $tracker;
        $this->uri                      = $uri;
        $this->label                    = $label;
        $this->columns                  = $columns;
        $this->resources                = $resources;
        $this->backlog                  = $backlog;
        $this->archive                  = $archive;
        $this->user_can_add_columns     = $user_can_add_columns;
        $this->user_can_reorder_columns = $user_can_reorder_columns;
        $this->user_can_add_artifact    = $user_can_add_artifact;
    }

    public static function build(
        AgileDashboard_Kanban $kanban,
        AgileDashboard_KanbanColumnFactory $column_factory,
        AgileDashboard_KanbanUserPreferences $user_preferences,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
        bool $user_can_add_columns,
        bool $user_can_reorder_columns,
        bool $user_can_add_in_place,
        bool $user_can_add_artifact,
        PFUser $user,
    ): self {
        $kanban_id  = $kanban->getId();
        $kanban_uri = self::ROUTE . '/' . $kanban_id;

        return new self(
            JsonCast::toInt($kanban_id),
            JsonCast::toInt($kanban->getTrackerId()),
            KanbanTrackerRepresentation::fromKanban(TrackerFactory::instance(), $kanban),
            $kanban_uri,
            $kanban->getName(),
            self::getColumns($kanban, $column_factory, $kanban_actions_checker, $user_can_add_in_place, $user),
            [
                'backlog' => [
                    'uri' => $kanban_uri . '/' . self::BACKLOG_ROUTE,
                ],
                'items' => [
                    'uri' => $kanban_uri . '/' . self::ITEMS_ROUTE,
                ],
            ],
            new KanbanBacklogInfoRepresentation('Backlog', $user_preferences->isBacklogOpen($kanban, $user), $user_can_add_in_place),
            new KanbanArchiveInfoRepresentation('Archive', $user_preferences->isArchiveOpen($kanban, $user)),
            $user_can_add_columns,
            $user_can_reorder_columns,
            $user_can_add_artifact
        );
    }

    /**
     * @return KanbanColumnRepresentation[]
     */
    private static function getColumns(
        AgileDashboard_Kanban $kanban,
        AgileDashboard_KanbanColumnFactory $column_factory,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
        bool $user_can_add_in_place,
        PFUser $user,
    ): array {
        $column_representations = [];
        $columns                = $column_factory->getAllKanbanColumnsForAKanban($kanban, $user);

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

            $column_representation = new KanbanColumnRepresentation($column, $user_can_add_in_place, $user_can_remove_column, $user_can_edit_label);

            $column_representations[] = $column_representation;
        }
        return $column_representations;
    }
}
