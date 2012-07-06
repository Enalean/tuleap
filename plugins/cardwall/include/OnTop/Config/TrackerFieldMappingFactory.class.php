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

class Cardwall_OnTop_Config_TrackerFieldMappingFactory {

    /** @var Tracker_FormElementFactory */
    private $factory;
    
    function __construct(Tracker_FormElementFactory $factory) {
        $this->factory = $factory;
    }

    public function newMapping(Tracker $tracker, $field_id) {
        $selected_field = $this->factory->getFieldById($field_id);
        $available_fields = $this->factory->getUsedSbFields($tracker);
        return new Cardwall_OnTop_Config_TrackerFieldMapping($tracker, $selected_field, $available_fields);
    }

}
?>
