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
class CardwallConvertorTest extends TestCase
{
    public function testItBuildsABasicRenderer(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer type="plugin_cardwall">
                <name>A cardwall</name>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new CardwallConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            'new \Tuleap\Cardwall\XML\XMLCardwallRenderer(\'A cardwall\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithIdAndDescription(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer ID="R123" type="plugin_cardwall">
                <name>A cardwall</name>
                <description>The description</description>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $convertor = new CardwallConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Cardwall\XML\XMLCardwallRenderer(\'A cardwall\'))->withId(\'R123\')->withDescription(\'The description\')',
            $printer->prettyPrint([$node])
        );
    }

    public function testItBuildsARendererWithFieldId(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <renderer field_id="F123" type="plugin_cardwall">
                <name>A cardwall</name>
            </renderer>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();
        $id_to_name_mapping->set('F123', 'Status');

        $convertor = new CardwallConvertor();
        $node      = $convertor->buildFromXml($xml, new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '(new \Tuleap\Cardwall\XML\XMLCardwallRenderer(\'A cardwall\'))->withField(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'Status\'))',
            $printer->prettyPrint([$node])
        );
    }
}
