<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tools\Xml2Php\Tracker\Report;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CriterionConvertorTest extends TestCase
{
    public function testItBuildsABasicCriterion(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <criteria rank="0">
                <field REF="F123"/>
            </criteria>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new CriterionConvertor();
        $node      = $convertor->buildFromXml($xml, $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReportCriterion(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')))->withRank(0)',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsAnAdvancedCriterion(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <criteria rank="0" is_advanced="1">
                <field REF="F123"/>
            </criteria>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new CriterionConvertor();
        $node      = $convertor->buildFromXml($xml, $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReportCriterion(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')))->withRank(0)->withIsAdvanced()',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsACriterionWithSelectedValues(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <criteria rank="0">
                <field REF="F123"/>
                <criteria_value type="list">
                    <selected_value REF="V1"/>
                    <selected_value REF="V2"/>
                </criteria_value>
            </criteria>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new CriterionConvertor();
        $node      = $convertor->buildFromXml($xml, $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReportCriterion(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')))->withRank(0)' .
            '->withSelectedValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V1\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V2\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsACriterionWithSelectedValuesAndNone(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <criteria rank="0">
                <field REF="F123"/>
                <criteria_value type="list">
                    <none_value/>
                    <selected_value REF="V1"/>
                    <selected_value REF="V2"/>
                </criteria_value>
            </criteria>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new CriterionConvertor();
        $node      = $convertor->buildFromXml($xml, $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\XML\XMLReportCriterion(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')))->withRank(0)' .
            '->withNoneSelected()->withSelectedValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V1\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V2\'))',
            $printer->prettyPrint([$node])
        );
    }
}
