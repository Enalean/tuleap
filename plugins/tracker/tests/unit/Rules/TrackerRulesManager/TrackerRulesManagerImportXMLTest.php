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

use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List_Factory;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

final class TrackerRulesManagerImportXMLTest extends TestCase
{
    public function testExportToXmlCallsRuleListFactoryExport()
    {
        $xml_data                     = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $sax_object                   = new SimpleXMLElement($xml_data);
        $xmlMapping                   = [];
        $tracker                      = TrackerTestBuilder::aTracker()->withId(45)->build();
        $form_element_factory         = $this->createMock(Tracker_FormElementFactory::class);
        $frozen_dao                   = $this->createMock(FrozenFieldsDao::class);
        $tracker_rules_list_validator = $this->createMock(TrackerRulesListValidator::class);
        $tracker_rules_date_validator = $this->createMock(TrackerRulesDateValidator::class);

        $tracker_factory = $this->createMock(TrackerFactory::class);

        $manager = new Tracker_RulesManager(
            $tracker,
            $form_element_factory,
            $frozen_dao,
            $tracker_rules_list_validator,
            $tracker_rules_date_validator,
            $tracker_factory,
            new NullLogger()
        );

        $date_factory = $this->createMock(Tracker_Rule_Date_Factory::class);
        $date_factory->expects($this->once())->method('exportToXml')->with($sax_object, $xmlMapping, 45);

        $list_factory = $this->createMock(Tracker_Rule_List_Factory::class);
        $list_factory->expects($this->once())->method('exportToXml')->with($sax_object, $xmlMapping, $form_element_factory, 45);

        $manager->setRuleDateFactory($date_factory);
        $manager->setRuleListFactory($list_factory);

        $manager->exportToXml($sax_object, $xmlMapping);
    }
}
