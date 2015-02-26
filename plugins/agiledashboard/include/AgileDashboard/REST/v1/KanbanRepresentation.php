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

namespace Tuleap\AgileDashboard\REST\v1;

use Tuleap\REST\JsonCast;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;

class KanbanRepresentation {

    const ROUTE         = 'kanban';
    const BACKLOG_ROUTE = 'backlog';
    const ITEMS_ROUTE   = 'items';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $tracker_id;

    /**
     * @var int
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array
     */
    public $columns;

    /*
     * @var array
     */
    public $resources;

    public function build(AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumnFactory $column_factory) {
        $this->id         = JsonCast::toInt($kanban->getId());
        $this->tracker_id = JsonCast::toInt($kanban->getTrackerId());
        $this->uri        = self::ROUTE.'/'.$this->id;
        $this->label      = $kanban->getName();
        $this->columns    = array();

        $this->setColumns($kanban, $column_factory);

        $this->resources = array(
            'backlog' => array(
                'uri' => $this->uri . '/'. self::BACKLOG_ROUTE
            ),
            'items' => array(
                'uri' => $this->uri . '/'. self::ITEMS_ROUTE
            )
        );
    }

    private function setColumns(AgileDashboard_Kanban $kanban, AgileDashboard_KanbanColumnFactory $column_factory) {
        $columns = $column_factory->getAllKanbanColumnsForAKanban($kanban);

        foreach ($columns as $column) {
            $column_representation = new KanbanColumnRepresentation();
            $column_representation->build($column);

            $this->columns[] = $column_representation;
        }
    }
}
