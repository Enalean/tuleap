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

require_once __DIR__.'/../../bootstrap.php';

class MoveChangesetXMLUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveChangesetXMLUpdater
     */
    private $updater;

    public function setUp()
    {
        parent::setUp();

        $this->initial_effort_factory = Mockery::mock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->updater                = new MoveChangesetXMLUpdater($this->initial_effort_factory);

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

        $this->source_tracker           = Mockery::mock(Tracker::class);
        $this->target_tracker           = Mockery::mock(Tracker::class);
        $source_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $target_initial_effort_field    = Mockery::mock(Tracker_FormElement_Field::class);
        $source_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $target_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);


        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($source_initial_effort_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($target_initial_effort_semantic);
        $source_initial_effort_semantic->shouldReceive('getField')->andReturn($source_initial_effort_field);
        $target_initial_effort_semantic->shouldReceive('getField')->andReturn($target_initial_effort_field);
        $source_initial_effort_field->shouldReceive('getName')->andReturn('effort');
        $target_initial_effort_field->shouldReceive('getName')->andReturn('effort_v2');
    }

    public function testItUpdatesTheFieldChangeAtGivenIndexIfItCorrespondsToInitialEffortField()
    {
        $index = 1;
        $this->updater->parseFieldChangeNodesAtGivenIndex($this->source_tracker, $this->target_tracker, $this->changeset_xml, $index);

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'effort_v2');
    }

    public function testItDoesNotUpdateTheFieldChangeAtGivenIndexIfItDoesNotCorrespondToInitialEffortField()
    {
        $index = 2;
        $this->updater->parseFieldChangeNodesAtGivenIndex($this->source_tracker, $this->target_tracker, $this->changeset_xml, $index);

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'details');

        $index = 0;
        $this->updater->parseFieldChangeNodesAtGivenIndex($this->source_tracker, $this->target_tracker, $this->changeset_xml, $index);

        $this->assertEquals((string) $this->changeset_xml->field_change[$index]['field_name'], 'summary');
    }
}
