<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Action\Move\NoFeedbackFieldCollector;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;

require_once __DIR__ . '/../../bootstrap.php';

class MoveChangesetXMLUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveChangesetXMLUpdater
     */
    private $updater;

    public function setUp(): void
    {
        parent::setUp();

        $this->initial_effort_factory = Mockery::mock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->form_element_factory   = Mockery::mock(Tracker_FormElementFactory::class);
        $this->field_value_matcher    = Mockery::mock(FieldValueMatcher::class);
        $this->updater                = new MoveChangesetXMLUpdater(
            $this->initial_effort_factory,
            $this->form_element_factory,
            $this->field_value_matcher
        );

        $this->collector = new NoFeedbackFieldCollector();

        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
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
            . '  </changeset>');

        $this->source_tracker                 = Mockery::mock(Tracker::class);
        $this->target_tracker                 = Mockery::mock(Tracker::class);
        $this->source_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->source_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->target_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);

        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_initial_effort_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_initial_effort_semantic);
        $this->source_initial_effort_field->shouldReceive('getName')->andReturn('effort');
        $this->target_initial_effort_field->shouldReceive('getName')->andReturn('effort_v2');
    }

    public function testItUpdatesTheFieldChangeAtGivenIndexIfItCorrespondsToInitialEffortField()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

        $this->field_value_matcher->shouldReceive('getMatchingValueByDuckTyping')->never();

        $index = 1;
        $this->updater->parseFieldChangeNodesAtGivenIndex(
            $this->source_tracker,
            $this->target_tracker,
            $this->changeset_xml,
            $index,
            $this->collector
        );

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'effort_v2');
    }

    public function testItDoesNotUpdateTheFieldChangeAtGivenIndexIfItDoesNotCorrespondToInitialEffortField()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

        $this->field_value_matcher->shouldReceive('getMatchingValueByDuckTyping')->never();

        $index = 2;
        $this->updater->parseFieldChangeNodesAtGivenIndex(
            $this->source_tracker,
            $this->target_tracker,
            $this->changeset_xml,
            $index,
            $this->collector
        );

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'details');

        $index = 0;
        $this->updater->parseFieldChangeNodesAtGivenIndex(
            $this->source_tracker,
            $this->target_tracker,
            $this->changeset_xml,
            $index,
            $this->collector
        );

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'summary');
    }

    public function testItUpdatesTheFieldChangeWithMatchingValueAtGivenIndexIfItCorrespondsToInitialEffortFieldList()
    {
        $changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="effort">'
            . '      <value format="id">125</value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details</value>'
            . '    </field_change>'
            . '  </changeset>');

        $source_initial_effort_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $target_initial_effort_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $source_initial_effort_field->shouldReceive('getName')->andReturn('effort');
        $target_initial_effort_field->shouldReceive('getName')->andReturn('effort_v2');

        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($source_initial_effort_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($target_initial_effort_field)->andReturn('sb');

        $this->field_value_matcher->shouldReceive('getMatchingValueByDuckTyping')->once()->andReturn(201);

        $index = 1;
        $this->updater->parseFieldChangeNodesAtGivenIndex(
            $this->source_tracker,
            $this->target_tracker,
            $changeset_xml,
            $index,
            $this->collector
        );

        $this->assertEquals((string) $changeset_xml->field_change[$index]['field_name'], 'effort_v2');
        $this->assertEquals((int) $changeset_xml->field_change[$index]->value, 201);
    }

    public function testItDoesNotAddNoneValueIfSourceInitialEffortListFieldDoesNotHaveValue()
    {
        $changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="effort">'
            . '      <value/>'
            . '    </field_change>'
            . '  </changeset>');

        $source_initial_effort_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $target_initial_effort_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $source_initial_effort_field->shouldReceive('getName')->andReturn('effort');
        $target_initial_effort_field->shouldReceive('getName')->andReturn('effort_v2');

        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($source_initial_effort_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($target_initial_effort_field)->andReturn('sb');

        $this->field_value_matcher->shouldReceive('getMatchingValueByDuckTyping')->never();

        $index = 0;
        $this->updater->parseFieldChangeNodesAtGivenIndex(
            $this->source_tracker,
            $this->target_tracker,
            $changeset_xml,
            $index,
            $this->collector
        );

        $this->assertEquals((string) $changeset_xml->field_change[$index]['field_name'], 'effort_v2');
        $this->assertEquals((int) $changeset_xml->field_change[$index]->value, 0);
    }
}
