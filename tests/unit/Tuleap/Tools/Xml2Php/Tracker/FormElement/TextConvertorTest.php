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


namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use PhpParser\PrettyPrinter;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TextConvertorTest extends TestCase
{
    public function testItBuildsABasicText(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="text" ID="F123">
                    <name>details</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new TextConvertor($xml->formElement, $xml, 'XMLText'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLText::fromTrackerAndName($tracker, \'details\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('details', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsATextWithLabel(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="text" ID="F123">
                    <name>details</name>
                    <label>Details</label>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new TextConvertor($xml->formElement, $xml, 'XMLText'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLText::fromTrackerAndName($tracker, \'details\')->withLabel(\'Details\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('details', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsATextWithRank(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="text" ID="F123" rank="2">
                    <name>details</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new TextConvertor($xml->formElement, $xml, 'XMLText'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLText::fromTrackerAndName($tracker, \'details\')->withRank(2)',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('details', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsATextWithPermissions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="text" ID="F123">
                    <name>details</name>
                </formElement>
                <permissions>
                    <permission scope="tracker" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_ACCESS_FULL"/>
                    <permission scope="field" REF="F120" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                    <permission scope="field" REF="F123" ugroup="UGROUP_ANONYMOUS" type="PLUGIN_TRACKER_FIELD_READ"/>
                    <permission scope="field" REF="F123" ugroup="UGROUP_REGISTERED" type="PLUGIN_TRACKER_FIELD_SUBMIT"/>
                </permissions>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new TextConvertor($xml->formElement, $xml, 'XMLText'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLText::fromTrackerAndName($tracker, \'details\')->withPermissions(' .
            'new \Tuleap\Tracker\FormElement\Field\XML\ReadPermission(\'UGROUP_ANONYMOUS\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\XML\SubmitPermission(\'UGROUP_REGISTERED\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('details', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsATextWithCustomRowsAndCols(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="text" ID="F123">
                    <name>details</name>
                    <properties rows="14" cols="62" />
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new TextConvertor($xml->formElement, $xml, 'XMLText'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLText::fromTrackerAndName($tracker, \'details\')->withRows(14)->withCols(62)',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('details', $id_to_name_mapping->get('F123'));
    }
}
