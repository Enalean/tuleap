<?php
/**
 * Copyright (c) Enalean, 2013-2017. All Rights Reserved.
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

class AgileDashboard_MilestonesCardwallRepresentation
{

    public const ROUTE = 'cardwall';

    /** @var array */
    public $columns;

    /** @var array */
    public $swimlanes;

    public function build(Cardwall_Board $board, $planning_id, PFUser $user)
    {
        $this->columns = $board->getColumns()->getRestValue();
        $this->swimlanes = array();
        foreach ($board->getSwimlines() as $swimline) {
            $swimline_representation = new AgileDashboard_SwimlineRepresentation();
            $swimline_representation->build($swimline, $planning_id, $user);
            $this->swimlanes[] = $swimline_representation;
        }
    }
}
