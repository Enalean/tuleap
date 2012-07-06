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

require_once 'MappingFieldValue.class.php';
require_once 'MappingFieldValueCollection.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/ColumnMappingFieldValueDao.class.php';

class Cardwall_OnTop_Config_MappingFieldValueCollectionFactory {

    /**
     * @var Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private $dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $element_factory;

    public function __construct(Cardwall_OnTop_ColumnMappingFieldValueDao $dao, Tracker_FormElementFactory $element_factory) {
        $this->dao             = $dao;
        $this->element_factory = $element_factory;
    }

    /**
     * @return Cardwall_OnTop_Config_MappingFieldValueCollection
     */
    public function getCollection(Tracker $tracker) {
        $collection = new Cardwall_OnTop_Config_MappingFieldValueCollection();
        foreach ($this->dao->searchMappingFieldValues($tracker->getId()) as $row) {
            //TODO: if $row['field_id'] is null, it means that we target the semantic status
            $field = $this->element_factory->getFieldById($row['field_id']);
            if ($field) {
                $collection->add(
                    new Cardwall_OnTop_Config_MappingFieldValue(
                        $tracker,
                        $field,
                        $row['value_id'],
                        $row['column_id']
                    )
                );
            }
        }
        return $collection;
    }
}
?>
