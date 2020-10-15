<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
final class Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    /** @var Tracker_XML_ChildrenCollector */
    private $collector;

    /** @var PFUser */
    private $user;

    protected function setUp(): void
    {
        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        TrackerFactory::setInstance($tracker_factory);

        $this->user = new PFUser(['language_id' => 'en']);

        $story_tracker  = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn(100)->getMock();
        $task_tracker   = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn(101)->getMock();
        $bug_tracker    = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn(102)->getMock();
        $dayoff_tracker = Mockery::spy(Tracker::class)->shouldReceive('getId')->andReturn(103)->getMock();
        $story_tracker->shouldReceive('getChildren')->andReturn([$task_tracker, $bug_tracker]);

        $tracker_factory->shouldReceive('getTrackerById')->with(100)->andReturns($story_tracker);
        $tracker_factory->shouldReceive('getTrackerById')->with(101)->andReturns($task_tracker);
        $tracker_factory->shouldReceive('getTrackerById')->with(102)->andReturns($bug_tracker);
        $tracker_factory->shouldReceive('getTrackerById')->with(103)->andReturns($dayoff_tracker);

        $this->collector = new Tracker_XML_ChildrenCollector();
        $this->field     = Mockery::spy(Tracker_FormElement_Field_File::class);
        $this->field->shouldReceive('getTracker')->andReturn($story_tracker);
        $this->field->shouldReceive('getName')->andReturn('artifact links');
        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter(
            $this->collector,
            $this->user
        );
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    protected function tearDown(): void
    {
        TrackerFactory::clearInstance();
    }

    public function testItExportsChildren(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCanView(111, 101, null),
            $this->anArtifactLinkInfoUserCanView(222, 102, null),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(false);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('artifact links', (string) $field_change['field_name']);
        $this->assertEquals('art_link', (string) $field_change['type']);

        $this->assertEquals(111, (int) $field_change->value[0]);
        $this->assertEquals(222, (int) $field_change->value[1]);
    }

    public function testItExportsChildrenNatureMode(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
            $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(true);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('artifact links', (string) $field_change['field_name']);
        $this->assertEquals('art_link', (string) $field_change['type']);

        $this->assertEquals(111, (int) $field_change->value[0]);
        $this->assertEquals(222, (int) $field_change->value[1]);
        $this->assertCount(2, $field_change->value);
    }

    public function testItDoesNotExportArtifactsThatAreNotChildren(): void
    {
        $this->changeset_value->shouldReceive('getArtifactIds')->andReturns([
            $this->anArtifactLinkInfoUserCanView(333, 103, null),
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotExportChildrenUserCannotSee(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCannotView(111, 101, null),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(false);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotExportChildrenUserCannotSeeNatureMode(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCannotView(111, 101, '_is_child'),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(true);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotFailIfNull(): void
    {
        $this->changeset_value->shouldReceive('getArtifactIds')->andReturns(null);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertCount(0, $field_change->value);
    }

    public function testItCollectsChildren(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCanView(111, 101, null),
            $this->anArtifactLinkInfoUserCanView(222, 102, null),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(false);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $this->assertEquals([111, 222], $this->collector->getAllChildrenIds());
    }

    public function testItCollectsChildrenNatureMode(): void
    {
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
            $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
        ]);

        $artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturns(true);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $this->assertEquals([111, 222], $this->collector->getAllChildrenIds());
    }

    private function anArtifactLinkInfoUserCanView($artifact_id, $tracker_id, $nature)
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $nature);
        $artifact_link_info->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        return $artifact_link_info;
    }

    private function anArtifactLinkInfoUserCannotView($artifact_id, $tracker_id, $nature)
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $nature);
        $artifact_link_info->shouldReceive('userCanView')->with($this->user)->andReturns(false);

        return $artifact_link_info;
    }

    private function anArtifactLinkInfo($artifact_id, $tracker_id, $nature)
    {
        return Mockery::mock(
            Tracker_ArtifactLinkInfo::class,
            [
                'getArtifactId' => $artifact_id,
                'getTracker'    => TrackerFactory::instance()->getTrackerById($tracker_id),
                'getNature'     => $nature,
            ]
        );
    }
}
