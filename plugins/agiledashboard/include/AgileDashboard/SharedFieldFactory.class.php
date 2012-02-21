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

require_once 'SharedFieldDao.class.php';
require_once 'SharedField.class.php';

class AgileDashboard_SharedFieldFactory {
    const VALUE_NONE = 100;
    
    public function getSharedFields($criteria) {
        $sharedFields = array();

        if ($criteria) {
            foreach($criteria as $fieldId => $data) {
                $valueIds    = $data['values'];
                
                if ($valueIds != array('')) {
                    $sharedFields[] = $this->getSharedField($fieldId, $valueIds);
                }
            }
        }
        
        return $sharedFields;
    }
    
    private function getSharedField($fieldId, $valueIds) {
        $sharedField = new AgileDashboard_SharedField();
        
        $this->addSharedFieldId($sharedField, $fieldId);
        $this->addSharedValueIds($sharedField, $valueIds);

        return $sharedField;
    }
    
    private function addSharedFieldId(AgileDashboard_SharedField $sharedField, $fieldId) {
        foreach ($this->getDao()->searchSharedFieldIds($fieldId) as $row) {
            $sharedField->addFieldId($row['id']);
        }
    }
    
    private function addSharedValueIds(AgileDashboard_SharedField $sharedField, array $valueIds) {
        $notNoneValueIds = $this->filterAndAddNoneValueIds($sharedField, $valueIds);

        foreach ($this->getDao()->searchSharedValueIds($notNoneValueIds ) as $row) {
            $sharedField->addValueId($row['id']);
        }
    }
    
    private function filterAndAddNoneValueIds(AgileDashboard_SharedField $sharedField, array $valueIds) {
        $notNoneValueIds = array();
        
        foreach($valueIds as $valueId) {
            if ($valueId == self::VALUE_NONE) {
                $sharedField->addValueId($valueId);
            } else {
                $notNoneValueIds[] = $valueId;
            }
        }
        
        return $notNoneValueIds;
    }
    
    protected function getDao() {
        return new AgileDashboard_SharedFieldDao();
    }
}

?>
