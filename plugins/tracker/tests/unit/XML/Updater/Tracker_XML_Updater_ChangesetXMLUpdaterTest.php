<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_XML_Updater_ChangesetXMLUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_XML_Updater_ChangesetXMLUpdater $updater;

    private SimpleXMLElement $artifact_xml;

    private Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor&MockObject $visitor;

    private array $submitted_values;

    private Tracker_FormElementFactory&MockObject $formelement_factory;

    private PFUser $user;

    private int $tracker_id = 123;

    private int $user_id = 101;

    private StringField $field_summary;

    private StringField $field_effort;

    private StringField $field_details;

    private Tracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                . '<artifact>'
                . '  <changeset>'
                . '    <submitted_on>2014</submitted_on>'
                . '    <submitted_by>123</submitted_by>'
                . '    <field_change field_name="summary">'
                . '      <value>Initial summary value</value>'
                . '    </field_change>'
                . '    <field_change field_name="effort">'
                . '      <value>125</value>'
                . '    </field_change>'
                . '    <field_change field_name="details">'
                . '      <value>Content of details</value>'
                . '    </field_change>'
                . '  </changeset>'
                . '</artifact>');
        $this->visitor      = $this->createMock(\Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor::class);
        $this->visitor->method('update');
        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->updater             = new Tracker_XML_Updater_ChangesetXMLUpdater($this->visitor, $this->formelement_factory);
        $this->user                = new PFUser(['user_id' => $this->user_id, 'language_id' => 'en']);
        $this->tracker             = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();
        $this->submitted_values    = [
            1001 => 'Content of summary field',
            1002 => '123',
        ];

        $this->field_summary = StringFieldBuilder::aStringField(1001)->build();
        $this->field_effort  = StringFieldBuilder::aStringField(1002)->build();
        $this->field_details = StringFieldBuilder::aStringField(1003)->build();
        $this->formelement_factory->method('getUsedFieldByNameForUser')
            ->willReturnCallback(fn (int $tracker_id, string $field_name, PFUser $user) => match ($field_name) {
                'summary' => $this->field_summary,
                'effort'  => $this->field_effort,
                'details' => $this->field_details,
            });
    }

    public function testItUpdatesTheSubmittedOnInformation(): void
    {
        $now = time();

        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, $now);

        $this->assertEquals((string) $this->artifact_xml->changeset->submitted_on, date('c', $now));
    }

    public function testItUpdatesTheSubmittedByInformation(): void
    {
        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, time());

        $this->assertEquals((int) $this->artifact_xml->changeset->submitted_by, $this->user->getId());
    }

    public function testItAsksToVisitorToUpdateSummaryAndEffortButNotDetailsBecauseItIsNotPartOfSubmittedValues(): void
    {
        $this->visitor
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(
                fn (SimpleXMLElement $field_change_xml, TrackerField $field, mixed $submitted_value) => match (true) {
                    $field === $this->field_summary &&
                    $submitted_value === 'Content of summary field',
                    $field === $this->field_effort &&
                    $submitted_value === '123' => true,
                }
            );

        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, time());
    }
}
