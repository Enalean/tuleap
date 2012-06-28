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
    public function getBoard($field_retriever, $field, $forests_of_artifacts) {
        $column_factory     = new Cardwall_ColumnFactory($field);
        $acc_field_provider = new Cardwall_AccumulatedStatusFieldsProvider();
        $mapping_collection = $column_factory->getMappings($acc_field_provider->accumulateStatusFields($forests_of_artifacts));
        
        // get presenters
        $forests_of_column_presenters = $this->transformIntoForestOfColumnPresenters($forests_of_artifacts, $field_retriever, $mapping_collection);
        
        // get columns
        $columns   = $column_factory->getColumns();
        
        // get swimlines
        $swimline_factory = new Cardwall_SwimlineFactory();
        $swimlines = $swimline_factory->getSwimlines($columns, $forests_of_column_presenters->getChildren());

        return new Cardwall_Board($swimlines, $columns, $mapping_collection);

    }

    private function transformIntoForestOfColumnPresenters($forests_of_artifacts, $field_retriever, $mapping_collection) {
        $column_id_visitor          = new TreeNodeMapper(new ColumnPresenterCallback($field_retriever, $mapping_collection));
        $card_presenter_visitor     = new TreeNodeMapper(new Cardwall_CreateCardPresenterCallback());
        $forests_of_card_presenters = $forests_of_artifacts->accept($card_presenter_visitor);
        return $forests_of_card_presenters->accept($column_id_visitor);
    }
}
?>
