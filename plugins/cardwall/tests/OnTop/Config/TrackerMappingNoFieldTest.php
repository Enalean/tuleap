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
require_once dirname(__FILE__) .'/../../bootstrap.php';

class Cardwall_OnTop_Config_TrackerMappingNoFieldTest extends TuleapTestCase
{

    public function itHasAnEmptyValueMappings()
    {
        $tracker          = aMockTracker()->build();
        $available_fields = array();
        $mapping = new Cardwall_OnTop_Config_TrackerMappingNoField($tracker, $available_fields);
        $this->assertEqual(array(), $mapping->getValueMappings());
    }

    public function itsFieldIsNull()
    {
        $tracker          = aMockTracker()->build();
        $available_fields = array();
        $mapping = new Cardwall_OnTop_Config_TrackerMappingNoField($tracker, $available_fields);
        $this->assertNull($mapping->getField());
    }
}
