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

require_once 'Column.class.php';
require_once 'Mapping.class.php';
require_once 'MappingCollection.class.php';

/**
 * Build from a SB field bunch of columns to display in cardwall
 */
class Cardwall_ColumnFactory {

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    /**
     * @var array of Cardwall_Column
     */
    private $columns = array();

    /**
     * @var Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
     */
    private $field_provider;
    
    public function __construct(Tracker_FormElement_Field_Selectbox                  $field,
                                Cardwall_FieldProviders_IProvideFieldGivenAnArtifact $field_provider) {
        $this->field = $field;
        $this->field_provider = $field_provider;
    }

    /**
     * @return array of Cardwall_Column
     */
    public function getColumns($config) {
        return $config->getCardwallColumns($config, $this->columns, $this->field, $this->field_provider);
    }
    
    

}

class Cardwall_Columns {

    private $columns;

    public function __construct(array $columns = array()) {
        $this->columns = $columns;
    }
    
    public function getColumnById($id) {
        foreach ($this->columns as $column) {
            if ($column->id == $id) {
                return $column;
            }
        }
    }

    public function getColumnByLabel($label) {
        foreach ($this->columns as $column) {
            if ($column->label == $label) {
                return $column;
            }
        }
    }
}
?>
