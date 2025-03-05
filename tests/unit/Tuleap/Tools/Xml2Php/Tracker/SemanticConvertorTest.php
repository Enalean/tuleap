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

namespace Tuleap\Tools\Xml2Php\Tracker;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SemanticConvertorTest extends TestCase
{
    public function testItBuildsTitleSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="title">
                <shortname>title</shortname>
                <label>Title</label>
                <description>Define the title of an artifact</description>
                <field REF="F123"/>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsDescriptionSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="description">
                <shortname>description</shortname>
                <label>Description</label>
                <description>Define the description of an artifact</description>
                <field REF="F123"/>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Tracker\Semantic\Description\XML\XMLDescriptionSemantic(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsContributorSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="contributor">
                <shortname>contributor</shortname>
                <label>Contributor/assignee</label>
                <description>Define the contributor/assignee of an artifact</description>
                <field REF="F123"/>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Tracker\Semantic\Contributor\XML\XMLContributorSemantic(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsStatusSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="status">
                <shortname>status</shortname>
                <label>Status</label>
                <description>Define the status of an artifact</description>
                <field REF="F123"/>
                <open_values>
                    <open_value REF="V1"/>
                    <open_value REF="V2"/>
                </open_values>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Semantic\Status\XML\XMLStatusSemantic(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')))' .
            '->withOpenValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V1\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V2\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsDoneSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="done">
                <shortname>done</shortname>
                <label>Done</label>
                <description>Define the closed status that are considered Done</description>
                <closed_values>
                    <closed_value REF="V1"/>
                    <closed_value REF="V2"/>
                </closed_values>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Semantic\Status\Done\XML\XMLDoneSemantic())->withDoneValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V1\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById(\'V2\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsTooltipSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="tooltip">
                <field REF="F123"/>
                <field REF="F124"/>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');
        $id_to_name_mapping->set('F124', 'Title');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Semantic\XML\XMLFieldsBasedSemantic(\'tooltip\'))->withFields(' .
            'new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'), ' .
            'new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Title\'))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsCardFieldsSemantic(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <semantic type="plugin_cardwall_card_fields">
                <field REF="F123"/>
                <field REF="F124"/>
            </semantic>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');
        $id_to_name_mapping->set('F124', 'Title');

        $convertor = new SemanticConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Semantic\XML\XMLFieldsBasedSemantic(\'plugin_cardwall_card_fields\'))->withFields(' .
            'new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'), ' .
            'new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Title\'))',
            $printer->prettyPrint([$node])
        );
    }
}
