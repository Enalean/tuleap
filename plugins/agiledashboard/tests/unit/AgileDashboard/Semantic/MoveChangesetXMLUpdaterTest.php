<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Action\Move\NoFeedbackFieldCollector;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveMatchingValueByDuckTypingStub;

final class MoveChangesetXMLUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private MoveChangesetXMLUpdater $updater;
    private Tracker_FormElementFactory&Mockery\MockInterface $form_element_factory;
    private RetrieveMatchingValueByDuckTyping $field_value_matcher;
    private NoFeedbackFieldCollector $collector;
    private SimpleXMLElement $changeset_xml;
    private Tracker $source_tracker;
    private Tracker $target_tracker;
    private Tracker_FormElement_Field&Mockery\MockInterface $source_initial_effort_field;
    private Tracker_FormElement_Field&Mockery\MockInterface $target_initial_effort_field;
    private AgileDashBoard_Semantic_InitialEffort&Mockery\MockInterface $source_initial_effort_semantic;
    private AgileDashBoard_Semantic_InitialEffort&Mockery\MockInterface $target_initial_effort_semantic;

    public function setUp(): void
    {
        parent::setUp();

        $initial_effort_factory     = Mockery::mock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->field_value_matcher  = RetrieveMatchingValueByDuckTypingStub::withMatchingValues([]);
        $this->updater              = new MoveChangesetXMLUpdater(
            $initial_effort_factory,
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

        $this->source_tracker                 = TrackerTestBuilder::aTracker()->build();
        $this->target_tracker                 = TrackerTestBuilder::aTracker()->build();
        $this->source_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->source_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->target_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);

        $initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_initial_effort_semantic);
        $initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_initial_effort_semantic);
        $this->source_initial_effort_field->shouldReceive('getName')->andReturn('effort');
        $this->target_initial_effort_field->shouldReceive('getName')->andReturn('effort_v2');
    }

    public function testItUpdatesTheFieldChangeAtGivenIndexIfItCorrespondsToInitialEffortField()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

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

    public function testItDoesNotUpdateTheFieldChangeAtGivenIndexIfItDoesNotCorrespondToInitialEffortField(): void
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);
        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

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

    public function testItUpdatesTheFieldChangeWithMatchingValueAtGivenIndexIfItCorrespondsToInitialEffortFieldList(): void
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

        $target_initial_effort_field->shouldReceive('getDefaultValue')->andReturn(201);
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

    public function testItDoesNotAddNoneValueIfSourceInitialEffortListFieldDoesNotHaveValue(): void
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
