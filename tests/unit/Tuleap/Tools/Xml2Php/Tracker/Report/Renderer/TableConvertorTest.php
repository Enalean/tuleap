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

namespace Tuleap\Tools\Xml2Php\Tracker\Report\Renderer;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TableConvertorTest extends TestCase
{
    public function testItBuildsABasicRenderer(): void
    {
        $xml = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="table">
                <name>A table</name>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new TableConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable(\'A table\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithIdAndDescription(): void
    {
        $xml = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer ID="R123" type="table">
                <name>A table</name>
                <description>The description</description>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new TableConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable(\'A table\'))->withId(\'R123\')->withDescription(\'The description\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithChunkSize(): void
    {
        $xml = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer chunksz="30" type="table">
                <name>A table</name>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new TableConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable(\'A table\'))->withChunkSize(30)',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithColumns(): void
    {
        $xml = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="table">
                <name>A table</name>
                <columns>
                    <field REF="F123" />
                    <field REF="F124" />
                </columns>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');
        $id_to_name_mapping->set('F124', 'Title');

        $convertor = new TableConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable(\'A table\'))->withColumns(' .
            'new \Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\')), ' .
            'new \Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Title\')))',
            $printer->prettyPrint([$node])
        );
    }
}
