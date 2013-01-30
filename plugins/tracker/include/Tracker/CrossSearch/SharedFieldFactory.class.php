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


class Tracker_CrossSearch_SharedFieldFactory {
    const VALUE_NONE = 100;
    
    public function getSharedFields($criteria) {
        $shared_fields = array();

        if ($criteria) {
            foreach($criteria as $field_id => $data) {
                $value_ids = $data['values'];
                
                if ($value_ids != array('')) {
                    $shared_fields[] = $this->getSharedField($field_id, $value_ids);
                }
            }
        }
        
        return $shared_fields;
    }
    
    private function getSharedField($field_id, $value_ids) {
        $shared_field = new Tracker_CrossSearch_SharedField();
        
        $this->addSharedFieldId($shared_field, $field_id);
        $this->addSharedValueIds($shared_field, $value_ids);

        return $shared_field;
    }
    
    private function addSharedFieldId(Tracker_CrossSearch_SharedField $shared_field, $field_id) {
        foreach ($this->getDao()->searchSharedFieldIds($field_id) as $row) {
            $shared_field->addFieldId($row['id']);
        }
    }
    
    private function addSharedValueIds(Tracker_CrossSearch_SharedField $shared_field, array $value_ids) {
        $not_none_value_ids = $this->filterAndAddNoneValueIds($shared_field, $value_ids);

        foreach ($this->getDao()->searchSharedValueIds($not_none_value_ids ) as $row) {
            $shared_field->addValueId($row['id']);
        }
    }
    
    private function filterAndAddNoneValueIds(Tracker_CrossSearch_SharedField $shared_field, array $value_ids) {
        $not_none_value_ids = array();
        
        foreach($value_ids as $value_id) {
            if ($value_id == self::VALUE_NONE) {
                $shared_field->addValueId($value_id);
            } else {
                $not_none_value_ids[] = $value_id;
            }
        }
        
        return $not_none_value_ids;
    }
    
    protected function getDao() {
        return new Tracker_CrossSearch_SharedFieldDao();
    }
}

?>
