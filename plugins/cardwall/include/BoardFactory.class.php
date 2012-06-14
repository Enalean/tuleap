<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'InjectDropIntoClassnamesVisitor.class.php';
require_once 'SwimlineFactory.class.php';
require_once 'ColumnFactory.class.php';
require_once 'Board.class.php';

/**
 * Builds Board given artifacts (for swimlines/cards) and a field (for columns)
 */
class Cardwall_BoardFactory {

    /**
     * @return Cardwall_Board
     */
    public function getBoard(Cardwall_InjectColumnIdVisitor $column_id_visitor, TreeNode $forest_of_artifacts, Tracker_FormElement_Field_Selectbox $field = null) {
        $swimline_factory = new Cardwall_SwimlineFactory();
        $column_factory   = new Cardwall_ColumnFactory($field);

        $forest_of_artifacts->accept($column_id_visitor);
        $accumulated_status_fields = $column_id_visitor->getAccumulatedStatusFields();

        $mappings = $column_factory->getMappings($accumulated_status_fields);

        $drop_into_visitor = new Cardwall_InjectDropIntoClassnamesVisitor($mappings);
        $forest_of_artifacts->accept($drop_into_visitor);

        $columns   = $column_factory->getColumns();
        $swimlines = $swimline_factory->getSwimlines($columns, $forest_of_artifacts->getChildren());

        return new Cardwall_Board($swimlines, $columns, $mappings);
    }
}
?>
