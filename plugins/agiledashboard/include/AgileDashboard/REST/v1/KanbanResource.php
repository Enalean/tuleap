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

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanNotFoundException;

class KanbanResource {

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Get kanban
     *
     * Get the definition of a given kanban
     *
     * @url GET {id}
     *
     * @param int $id Id of the kanban
     *
     * @return Tuleap\AgileDashboard\REST\v1\KanbanRepresentation
     *
     * @throws 404
     */
    protected function getId($id) {
        $kanban_factory = new AgileDashboard_KanbanFactory(new AgileDashboard_KanbanDao());
        try {
            $kanban = $kanban_factory->getKanban($id);
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            throw new RestException(404);
        }
        Header::allowOptionsGet();

        $kanban_representation = new KanbanRepresentation();
        $kanban_representation->build($kanban);

        return $kanban_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     */
    public function optionsId($id) {
        Header::allowOptionsGet();
    }
}
