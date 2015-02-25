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
use TrackerFactory;
use AgileDashboard_Kanban;
use Tracker_Semantic_Status;

class KanbanRepresentation {

    const ROUTE         = 'kanban';
    const BACKLOG_ROUTE = 'backlog';
    const ITEMS_ROUTE   = 'items';

    /**
     * @var Int
     */
    public $id;

    /**
     * @var Int
     */
    public $tracker_id;

    /**
     * @var Int
     */
    public $uri;

    /**
     * @var String
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

    public function build(AgileDashboard_Kanban $kanban) {
        $this->id         = JsonCast::toInt($kanban->getId());
        $this->tracker_id = JsonCast::toInt($kanban->getTrackerId());
        $this->uri        = self::ROUTE.'/'.$this->id;
        $this->label      = $kanban->getName();
        $this->columns    = array();

        $this->setColumns($kanban);

        $this->resources = array(
            'backlog' => array(
                'uri' => $this->uri . '/'. self::BACKLOG_ROUTE
            ),
            'items' => array(
                'uri' => $this->uri . '/'. self::ITEMS_ROUTE
            )
        );
    }

    private function setColumns(AgileDashboard_Kanban $kanban) {
        $semantic     = $this->getSemanticStatus($kanban);
        if (! $semantic) {
            return;
        }

        $field_values = $this->getFieldValues($semantic);
        $open_values  = $this->getOpenValues($semantic);
        foreach ($open_values as $id) {
            if (isset($field_values[$id])) {
                $this->columns[] = array(
                    'id'        => JsonCast::toInt($id),
                    'label'     => $field_values[$id]->getLabel(),
                    'is_open'   => true,
                    'limit'     => null,
                    'color'     => null
                );
            }
        }
    }

    private function getOpenValues(Tracker_Semantic_Status $semantic) {
        return $semantic->getOpenValues();
    }

    private function getFieldValues(Tracker_Semantic_Status $semantic) {
        return $semantic->getField()->getAllValues();
    }

    private function getSemanticStatus(AgileDashboard_Kanban $kanban) {
        $tracker = TrackerFactory::instance()->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            return;
        }

        $semantic = Tracker_Semantic_Status::load($tracker);
        if (! $semantic->getFieldId()) {
            return;
        }

        return $semantic;
    }
}
