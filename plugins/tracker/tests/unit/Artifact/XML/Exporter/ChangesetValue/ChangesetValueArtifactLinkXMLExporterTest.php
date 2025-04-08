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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;
use Tracker_XML_ChildrenCollector;
use TrackerFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueArtifactLinkXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ChangesetValueArtifactLinkXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_Changeset $changeset;

    private ArtifactLinkField $field;

    private Tracker_XML_ChildrenCollector $collector;

    private PFUser $user;

    protected function setUp(): void
    {
        $tracker_factory = $this->createMock(\TrackerFactory::class);
        TrackerFactory::setInstance($tracker_factory);

        $this->user = new PFUser(['language_id' => 'en']);

        $story_tracker  = TrackerTestBuilder::aTracker()->withId(100)->build();
        $task_tracker   = TrackerTestBuilder::aTracker()->withId(101)->build();
        $bug_tracker    = TrackerTestBuilder::aTracker()->withId(102)->build();
        $dayoff_tracker = TrackerTestBuilder::aTracker()->withId(103)->build();
        $story_tracker->setChildren([$task_tracker, $bug_tracker]);

        $tracker_factory->method('getTrackerById')->willReturnMap([
            [100, $story_tracker],
            [101, $task_tracker],
            [102, $bug_tracker],
            [103, $dayoff_tracker],
        ]);

        $this->collector     = new Tracker_XML_ChildrenCollector();
        $this->field         = ArtifactLinkFieldBuilder::anArtifactLinkField(1001)
            ->inTracker($story_tracker)
            ->withName('artifact links')
            ->build();
        $this->exporter      = new ChangesetValueArtifactLinkXMLExporter(
            $this->collector,
            $this->user
        );
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $this->changeset = ChangesetTestBuilder::aChangeset(101)->build();
    }

    protected function tearDown(): void
    {
        TrackerFactory::clearInstance();
    }

    public function testItExportsChildren(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                111 => $this->anArtifactLinkInfoUserCanView(111, 101, null),
                222 => $this->anArtifactLinkInfoUserCanView(222, 102, null),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $tracker->method('getId')->willReturn(101);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('artifact links', (string) $field_change['field_name']);
        $this->assertEquals('art_link', (string) $field_change['type']);

        $this->assertEquals(111, (int) $field_change->value[0]);
        $this->assertEquals(222, (int) $field_change->value[1]);
    }

    public function testItExportsChildrenTypeMode(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                111 => $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
                222 => $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $tracker->method('getId')->willReturn(101);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEquals('artifact links', (string) $field_change['field_name']);
        $this->assertEquals('art_link', (string) $field_change['type']);

        $this->assertEquals(111, (int) $field_change->value[0]);
        $this->assertEquals(222, (int) $field_change->value[1]);
        $this->assertCount(2, $field_change->value);
    }

    public function testNoArtifactLinks(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotExportChildrenUserCannotSee(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                $this->anArtifactLinkInfoUserCannotView(111, 101, null),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $tracker->method('getId')->willReturn(101);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotExportChildrenUserCannotSeeTypeMode(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                $this->anArtifactLinkInfoUserCannotView(111, 101, '_is_child'),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertCount(0, $field_change->value);
    }

    public function testItDoesNotFailIfNull(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([])->build();


        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $changeset_value,
            []
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertCount(0, $field_change->value);
    }

    public function testItCollectsChildren(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                $this->anArtifactLinkInfoUserCanView(111, 101, null),
                $this->anArtifactLinkInfoUserCanView(222, 102, null),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $tracker->method('getId')->willReturn(101);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $this->assertEquals([111, 222], $this->collector->getAllChildrenIds());
    }

    public function testItCollectsChildrenTypeMode(): void
    {
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $this->field)
            ->withLinks([
                $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
                $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
            ])->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $tracker->method('getId')->willReturn(101);
        $artifact = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $changeset_value,
            []
        );

        $this->assertEquals([111, 222], $this->collector->getAllChildrenIds());
    }

    private function anArtifactLinkInfoUserCanView(int $artifact_id, int $tracker_id, ?string $type): Tracker_ArtifactLinkInfo
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $type);
        $artifact_link_info->method('userCanView')->with($this->user)->willReturn(true);

        return $artifact_link_info;
    }

    private function anArtifactLinkInfoUserCannotView(int $artifact_id, int $tracker_id, ?string $type): Tracker_ArtifactLinkInfo
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $type);
        $artifact_link_info->method('userCanView')->with($this->user)->willReturn(false);

        return $artifact_link_info;
    }

    private function anArtifactLinkInfo(int $artifact_id, int $tracker_id, ?string $type): Tracker_ArtifactLinkInfo&MockObject
    {
        $artifact_link_info = $this->createMock(Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->method('getArtifactId')->willReturn($artifact_id);
        $artifact_link_info->method('getTrackerId')->willReturn($tracker_id);
        $artifact_link_info->method('getType')->willReturn($type);

        return $artifact_link_info;
    }
}
