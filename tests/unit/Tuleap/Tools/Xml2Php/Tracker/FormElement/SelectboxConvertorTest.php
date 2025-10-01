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
class SelectboxConvertorTest extends TestCase
{
    public function testItBuildsABasicSelectbox(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithLabel(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <label>Status</label>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withLabel(\'Status\')',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithRank(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123" rank="2">
                    <name>status</name>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withRank(2)',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithPermissions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
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

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withPermissions(' .
            'new \Tuleap\Tracker\FormElement\Field\XML\ReadPermission(\'UGROUP_ANONYMOUS\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\XML\SubmitPermission(\'UGROUP_REGISTERED\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithUsersValues(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>assigned_to</name>
                    <bind type="users">
                        <items>
                            <item label="group_members" />
                            <item label="group_admins" />
                        </items>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'assigned_to\')->withUsersValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue(\'group_members\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue(\'group_admins\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('assigned_to', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithStaticValues(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <bind type="static" is_rank_alpha="0">
                        <items>
                            <item ID="V13624" label="Low impact" />
                            <item ID="V13625" label="Major impact" />
                            <item ID="V13626" label="Critical impact" />
                        </items>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withStaticValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13624\', \'Low impact\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13625\', \'Major impact\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13626\', \'Critical impact\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithDefaultStaticValues(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <bind type="static" is_rank_alpha="0">
                        <items>
                            <item ID="V13624" label="Low impact" />
                            <item ID="V13625" label="Major impact" />
                            <item ID="V13626" label="Critical impact" />
                        </items>
                        <default_values>
                            <value REF="V13624"/>
                        </default_values>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withStaticValues(' .
            '(new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13624\', \'Low impact\'))->withIsDefault(), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13625\', \'Major impact\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13626\', \'Critical impact\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithAlphabeticallySortedStaticValues(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <bind type="static" is_rank_alpha="1">
                        <items>
                            <item ID="V13624" label="Low impact" />
                            <item ID="V13625" label="Major impact" />
                            <item ID="V13626" label="Critical impact" />
                        </items>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withAlphanumericRank()->withStaticValues(' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13624\', \'Low impact\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13625\', \'Major impact\'), ' .
            'new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13626\', \'Critical impact\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithStaticValuesWithDescriptions(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <bind type="static" is_rank_alpha="0">
                        <items>
                            <item ID="V13624" label="Low impact">
                                <description>Very low, such wow</description>
                            </item>
                        </items>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withStaticValues(' .
            '(new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13624\', \'Low impact\'))->withDescription(\'Very low, such wow\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }

    public function testItBuildsASelectboxWithStaticValuesWithDecorators(): void
    {
        $xml = simplexml_load_string(<<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <tracker>
                <formElement type="sb" ID="F123">
                    <name>status</name>
                    <bind type="static" is_rank_alpha="0">
                        <items>
                            <item ID="V13624" label="Low impact" />
                        </items>
                        <decorators>
                            <decorator REF="V13624" tlp_color_name="graffiti-yellow"/>
                        </decorators>
                    </bind>
                </formElement>
            </tracker>
            EOS
        );

        $id_to_name_mapping = new IdToNameMapping();

        $node = (new SelectboxConvertor($xml->formElement, $xml, 'XMLSelectbox'))
            ->get(new \Psr\Log\NullLogger(), $id_to_name_mapping);

        $printer = new PrettyPrinter\Standard();
        self::assertEquals(
            '\XMLSelectbox::fromTrackerAndName($tracker, \'status\')->withStaticValues(' .
            '(new \Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue(\'V13624\', \'Low impact\'))->withDecorator(\'graffiti-yellow\'))',
            $printer->prettyPrint([$node])
        );
        self::assertEquals('status', $id_to_name_mapping->get('F123'));
    }
}
