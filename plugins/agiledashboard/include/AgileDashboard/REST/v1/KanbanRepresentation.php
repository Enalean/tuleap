<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

    const ROUTE = 'kanban';

    /**
     * @var Int
     */
    public $id;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var String
     */
    public $label;

    /**
     * @var Int
     */
    public $nb_open;

    /**
     * @var Int
     */
    public $nb_closed;

    public function build(AgileDashboard_Kanban $kanban) {
        $this->id        = JsonCast::toInt($kanban->getTrackerId());
        $this->label     = $kanban->getName();
        $this->columns   = array();
        $this->nb_open   = 0;
        $this->nb_closed = 0;

        $tracker = TrackerFactory::instance()->getTrackerById($kanban->getTrackerId());
        if (! $tracker) {
            return;
        }

        $semantic     = Tracker_Semantic_Status::load($tracker);
        $status_field = $semantic->getField();
        if (! $status_field) {
            return;
        }

        $field_values = $status_field->getAllValues();
        $open_values  = $semantic->getOpenValues();
        foreach ($open_values as $id) {
            if (isset($field_values[$id])) {
                $this->columns[] = array(
                    'id'        => $id,
                    'label'     => $field_values[$id]->getLabel(),
                    'is_open'   => true,
                    'limit'     => null,
                    'color'     => null
                );
            }
        }
    }
}
