<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

use Mockery;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

class TrackerRulesManagerImportXMLTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testExportToXmlCallsRuleListFactoryExport()
    {
        $xml_data                     = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $sax_object                   = new SimpleXMLElement($xml_data);
        $xmlMapping                   = [];
        $tracker                      = \Mockery::mock(Tracker::class);
        $form_element_factory         = \Mockery::mock(\Tracker_FormElementFactory::class);
        $frozen_dao                   = Mockery::mock(FrozenFieldsDao::class);
        $tracker_rules_list_validator = Mockery::mock(TrackerRulesListValidator::class);
        $tracker_rules_date_validator = \Mockery::mock(TrackerRulesDateValidator::class);

        $tracker_factory = Mockery::mock(TrackerFactory::class);

        $manager = new Tracker_RulesManager(
            $tracker,
            $form_element_factory,
            $frozen_dao,
            $tracker_rules_list_validator,
            $tracker_rules_date_validator,
            $tracker_factory
        );

        $tracker->shouldReceive('getId')->andReturn(45);

        $date_factory = \Mockery::mock(\Tracker_Rule_Date_Factory::class);
        $date_factory->shouldReceive('exportToXml')->withArgs([$sax_object, $xmlMapping, 45])->once();

        $list_factory = \Mockery::mock(\Tracker_Rule_List_Factory::class);
        $list_factory->shouldReceive('exportToXml')->withArgs([$sax_object, $xmlMapping, $form_element_factory, 45])->once();

        $manager->setRuleDateFactory($date_factory);
        $manager->setRuleListFactory($list_factory);

        $manager->exportToXml($sax_object, $xmlMapping);
    }
}
