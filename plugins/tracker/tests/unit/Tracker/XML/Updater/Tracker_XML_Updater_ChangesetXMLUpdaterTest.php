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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Updater_ChangesetXMLUpdaterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Updater_ChangesetXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor */
    private $visitor;

    /** @var array */
    private $submitted_values;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var PFUser */
    private $user;

    /** @var int */
    private $tracker_id = 123;

    /** @var int */
    private $user_id = 101;

    /** @var Tracker_FormElement_Field */
    private $field_summary;

    /** @var Tracker_FormElement_Field */
    private $field_effort;

    /** @var Tracker_FormElement_Field */
    private $field_details;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml        = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
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
        $this->visitor             = \Mockery::spy(\Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->updater             = new Tracker_XML_Updater_ChangesetXMLUpdater($this->visitor, $this->formelement_factory);
        $this->user                = new PFUser(['user_id' => $this->user_id, 'language_id' => 'en']);
        $this->tracker             = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn($this->tracker_id)->getMock();
        $this->submitted_values    = [
            1001 => 'Content of summary field',
            1002 => '123'
        ];

        $this->field_summary = Mockery::spy(Tracker_FormElement_Field_String::class);
        $this->field_summary->shouldReceive('getId')->andReturn(1001);
        $this->field_summary->shouldReceive('getName')->andReturn('summary');
        $this->field_effort  = Mockery::spy(Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturn(1002)->getMock();
        $this->field_details = Mockery::spy(Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturn(1003)->getMock();
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->with($this->tracker_id, 'summary', $this->user)->andReturns($this->field_summary);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->with($this->tracker_id, 'effort', $this->user)->andReturns($this->field_effort);
        $this->formelement_factory->shouldReceive('getUsedFieldByNameForUser')->with($this->tracker_id, 'details', $this->user)->andReturns($this->field_details);
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

    public function testItAsksToVisitorToUpdateSummary(): void
    {
        $this->visitor->shouldReceive('update')->with($this->artifact_xml->changeset->field_change[0], $this->field_summary, 'Content of summary field')->ordered();

        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, time());
    }

    public function testItAsksToVisitorToUpdateEffort(): void
    {
        $this->visitor->shouldReceive('update')->with($this->artifact_xml->changeset->field_change[1], $this->field_effort, '123')->ordered();

        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, time());
    }

    public function testItDoesNotUpdateFieldIfTheyAreNotSubmitted(): void
    {
        $this->visitor->shouldReceive('update')->times(2);

        $this->updater->update($this->tracker, $this->artifact_xml, $this->submitted_values, $this->user, time());
    }
}
