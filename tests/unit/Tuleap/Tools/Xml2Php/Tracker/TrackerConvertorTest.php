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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TrackerConvertorTest extends TestCase
{
    public function testItBuildsABasicTracker(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithName(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <name><![CDATA[Issues]]></name>
                <item_name>issue</item_name>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = (new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\'))->withName(\'Issues\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithColor(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
                <color>lake-placid-blue</color>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = (new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\'))->withColor(\Tuleap\Color\ColorName::fromName(\'lake-placid-blue\'));',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithDescription(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
                <description><![CDATA[requests, bugs, tasks, activities]]></description>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = (new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\'))->withDescription(\'requests, bugs, tasks, activities\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithSubmitInstructions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
                <submit_instructions>Instructions</submit_instructions>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = (new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\'))->withSubmitInstructions(\'Instructions\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithBrowseInstructions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
                <browse_instructions>Instructions</browse_instructions>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = (new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\'))->withBrowseInstructions(\'Instructions\');',
            $printer->prettyPrint($nodes)
        );
    }

    public function testItBuildsATrackerWithSimpleWorkflow(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker id="T448">
                <item_name>issue</item_name>
                <formElements>
                    <formElement type="sb" ID="F123">
                        <name>status</name>
                        <bind type="static" is_rank_alpha="0">
                            <items />
                        </bind>
                    </formElement>
                </formElements>
                <simple_workflow>
                    <field_id REF="F123"/>
                    <is_used>1</is_used>
                </simple_workflow>
            </tracker>
            EOS
        );

        $nodes = TrackerConvertor::buildFromXml($xml)->get(new \Psr\Log\NullLogger());

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '$tracker = new \Tuleap\Tracker\XML\XMLTracker(\'T448\', \'issue\');' . PHP_EOL .
            '$my_awesome_tracker = $tracker->withFormElement(\Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField::fromTrackerAndName($tracker, \'status\'))->withWorkflow(' .
            '(new \Tuleap\Tracker\Workflow\XML\XMLSimpleWorkflow())->withField(new \Tuleap\Tracker\FormElement\XML\XMLReferenceByName(\'status\'))->withIsUsed());',
            $printer->prettyPrint($nodes)
        );
    }
}
