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
class GraphOnTrackersConvertorTest extends TestCase
{
    public function testItBuildsABasicRenderer(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="plugin_cardwall">
                <name>Graphs</name>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new GraphOnTrackersConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer(\'Graphs\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithIdAndDescription(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer ID="R123" type="plugin_cardwall">
                <name>Graphs</name>
                <description>The description</description>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new GraphOnTrackersConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer(\'Graphs\'))->withId(\'R123\')->withDescription(\'The description\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithPieChart(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="plugin_cardwall">
                <name>Graphs</name>
                <charts>
                    <chart type="pie" width="800" height="600" rank="1" base="F123">
                        <title>Pie chart</title>
                        <description>Awesome pie chart</description>
                    </chart>
                </charts>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'status');

        $convertor = new GraphOnTrackersConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer(\'Graphs\'))->withCharts(' .
            '(new \Tuleap\GraphOnTrackersV5\XML\XMLPieChart(800, 600, 1, \'Pie chart\'))' .
            '->withDescription(\'Awesome pie chart\')' .
            '->withBase(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'status\')))',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithBarChart(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="plugin_cardwall">
                <name>Graphs</name>
                <charts>
                    <chart type="bar" width="800" height="600" rank="1" base="F123" group="F124">
                        <title>Bar chart</title>
                        <description>Awesome bar chart</description>
                    </chart>
                </charts>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'status');
        $id_to_name_mapping->set('F124', 'title');

        $convertor = new GraphOnTrackersConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer(\'Graphs\'))->withCharts(' .
            '(new \Tuleap\GraphOnTrackersV5\XML\XMLBarChart(800, 600, 1, \'Bar chart\'))' .
            '->withDescription(\'Awesome bar chart\')' .
            '->withBase(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'status\'))' .
            '->withGroup(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'title\')))',
            $printer->prettyPrint([$node])
        );
    }
}
