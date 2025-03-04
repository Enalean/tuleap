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
class DateConvertorTest extends TestCase
{
    public function testItBuildsABasicDate(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="date" ID="F123">
                    <name>start_date</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new DateConvertor($xml->formElement, $xml, 'XMLDate'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLDate::fromTrackerAndName($tracker, \'start_date\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('start_date', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsADateWithLabel(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="date" ID="F123">
                    <name>start_date</name>
                    <label>Start date</label>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new DateConvertor($xml->formElement, $xml, 'XMLDate'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLDate::fromTrackerAndName($tracker, \'start_date\')->withLabel(\'Start date\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('start_date', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsADateWithRank(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="date" ID="F123" rank="2">
                    <name>start_date</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new DateConvertor($xml->formElement, $xml, 'XMLDate'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLDate::fromTrackerAndName($tracker, \'start_date\')->withRank(2)',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('start_date', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsADateWithPermissions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="date" ID="F123">
                    <name>start_date</name>
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

        $node = (new DateConvertor($xml->formElement, $xml, 'XMLDate'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLDate::fromTrackerAndName($tracker, \'start_date\')->withPermissions(' .
            'new \Tuleap\Tracker\FormElement\Field\XML\ReadPermission(\'UGROUP_ANONYMOUS\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\XML\SubmitPermission(\'UGROUP_REGISTERED\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('start_date', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsADateTime(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="date" ID="F123">
                    <name>start_date</name>
                    <properties display_time="1" />
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new DateConvertor($xml->formElement, $xml, 'XMLDate'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLDate::fromTrackerAndName($tracker, \'start_date\')->withDateTime()',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('start_date', $id_to_name_mapping->get('F123'));
    }
}
