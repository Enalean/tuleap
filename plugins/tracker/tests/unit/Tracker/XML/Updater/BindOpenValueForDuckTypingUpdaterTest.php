<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingValueByDuckTypingStub;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;
use XML_SimpleXMLCDATAFactory;

final class BindOpenValueForDuckTypingUpdaterTest extends TestCase
{
    private \Tracker_FormElement_Field_Selectbox $source_field;
    private \Tracker_FormElement_Field_Selectbox&\PHPUnit\Framework\MockObject\MockObject $target_field;

    protected function setUp(): void
    {
        $this->source_field = ListFieldBuilder::aListField(1)->build();
        $this->target_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
    }

    public function testItBindsOpenValueWithUserWrittenValues(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = "test";

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues(["test" => 309]);
        $updater             = new BindOpenValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory());

        $updater->updateOpenValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame("test", (string) $changeset_xml->field_change[0]->value);
        self::assertSame("label", (string) $changeset_xml->field_change[0]->value["format"]);
    }

    public function testItBindsOpenValueWithStaticValues(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = "b101";

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([101 => 309]);
        $updater             = new BindOpenValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory());

        $updater->updateOpenValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame("309", (string) $changeset_xml->field_change[0]->value);
        self::assertSame("id", (string) $changeset_xml->field_change[0]->value["format"]);
    }

    public function testItBindsValueWithDefaultValue(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = "";

        $field_value_matcher = RetrieveMatchingValueByDuckTypingStub::withoutAnyMatchingValue();
        $updater             = new BindOpenValueForDuckTypingUpdater($field_value_matcher, new MoveChangesetXMLUpdater(), new XML_SimpleXMLCDATAFactory());

        $this->target_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->method("getDefaultValue")->willReturn("b309");
        $updater->updateOpenValueForDuckTypingMove($changeset_xml, $this->source_field, $this->target_field, 0);
        self::assertSame("309", (string) $changeset_xml->field_change[0]->value);
        self::assertSame("id", (string) $changeset_xml->field_change[0]->value["format"]);
    }
}
