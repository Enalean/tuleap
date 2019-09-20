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
require_once __DIR__ .'/../bootstrap.php';

class Cardwall_OnTop_MappedFieldProviderTest extends TuleapTestCase
{

    public function itProvidesTheStatusFieldIfNoMapping()
    {
        $tracker  = \Mockery::spy(\Tracker::class);

        $status_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = mockery_stub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class)->getField()->returns($status_field);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider(\Mockery::spy(\Cardwall_OnTop_Config::class), $status_retriever);

        $this->assertEqual($status_field, $provider->getField($tracker));
    }

    public function itProvidesTheMappedFieldIfThereIsAMapping()
    {
        $tracker  = aTracker()->build();

        $mapped_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = \Mockery::spy(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $mapping = mockery_stub(\Cardwall_OnTop_Config_TrackerMapping::class)->getField()->returns($mapped_field);
        $config = mockery_stub(\Cardwall_OnTop_Config::class)->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        $this->assertEqual($mapped_field, $provider->getField($tracker));
    }

    public function itReturnsNullIfThereIsACustomMappingButNoFieldChoosenYet()
    {
        $tracker  = aTracker()->build();

        $status_field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class);
        $status_retriever = mockery_stub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class)->getField()->returns($status_field);
        $mapping = mockery_stub(\Cardwall_OnTop_Config_TrackerMapping::class)->getField()->returns(null);
        $config = mockery_stub(\Cardwall_OnTop_Config::class)->getMappingFor($tracker)->returns($mapping);
        $provider = new Cardwall_OnTop_Config_MappedFieldProvider($config, $status_retriever);

        $this->assertEqual(null, $provider->getField($tracker));
    }
}
