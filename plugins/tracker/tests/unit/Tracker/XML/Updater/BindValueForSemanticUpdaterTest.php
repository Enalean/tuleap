<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
use Tracker_FormElement_Field_List_Bind_Static;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollector;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingValueByDuckTypingStub;

final class BindValueForSemanticUpdaterTest extends TestCase
{
    private \Tracker_FormElement_Field_Selectbox $source_field;
    private \Tracker_FormElement_Field_Selectbox $target_field;
    private const BIND_DEFAULT_VALUE = "200";
    private \PHPUnit\Framework\MockObject\Stub|Tracker_FormElement_Field_List_Bind_Static $bind;

    protected function setUp(): void
    {
        $this->bind         = $this->createStub(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->source_field = TrackerFormElementListFieldBuilder::aListField(1)->build();
        $this->target_field = TrackerFormElementListFieldBuilder::aListField(1)->withBind($this->bind)->build();
    }

    public function testItDoesNotSetBindValueWhenXmlValueIsZero(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = 0;

        $field_value_matcher      = RetrieveMatchingValueByDuckTypingStub::withoutValue();
        $updater                  = new BindValueForSemanticUpdater($field_value_matcher);
        $feedback_field_collector = new FeedbackFieldCollector();

        $updater->updateValueForSemanticMove($changeset_xml, $this->source_field, $this->target_field, 0, $feedback_field_collector);
        $this->assertSame("0", (string) $changeset_xml->field_change[0]->value);
        $this->assertEmpty($feedback_field_collector->getFieldsPartiallyMigrated());
    }

    public function testWhenBindValueIsNotFoundDefaultValueIsSetAndFieldIsAddedToPartiallyMigrated(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = 101;

        $field_value_matcher      = RetrieveMatchingValueByDuckTypingStub::withoutValue();
        $updater                  = new BindValueForSemanticUpdater($field_value_matcher);
        $feedback_field_collector = new FeedbackFieldCollector();

        $this->bind->method('getDefaultValues')->willReturn([self::BIND_DEFAULT_VALUE => self::BIND_DEFAULT_VALUE]);


        $updater->updateValueForSemanticMove($changeset_xml, $this->source_field, $this->target_field, 0, $feedback_field_collector);
        $this->assertSame(self::BIND_DEFAULT_VALUE, (string) $changeset_xml->field_change[0]->value);
        $this->assertContains($this->source_field, $feedback_field_collector->getFieldsPartiallyMigrated());
    }

    public function testItSetBindValue(): void
    {
        $xml                 = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';
        $changeset_xml       = new SimpleXMLElement($xml);
        $field_change        = $changeset_xml->addChild("field_change");
        $field_change->value = 101;

        $field_value_matcher      = RetrieveMatchingValueByDuckTypingStub::withValue(101);
        $updater                  = new BindValueForSemanticUpdater($field_value_matcher);
        $feedback_field_collector = new FeedbackFieldCollector();

        $updater->updateValueForSemanticMove($changeset_xml, $this->source_field, $this->target_field, 0, $feedback_field_collector);
        $this->assertSame("101", (string) $changeset_xml->field_change[0]->value);
        $this->assertEmpty($feedback_field_collector->getFieldsPartiallyMigrated());
    }
}
