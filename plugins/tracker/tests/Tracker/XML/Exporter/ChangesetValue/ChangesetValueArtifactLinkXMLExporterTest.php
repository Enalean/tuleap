<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
require_once __DIR__ . '/../../../../bootstrap.php';

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporterTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();
        $tracker_factory = mock('TrackerFactory');
        TrackerFactory::setInstance($tracker_factory);

        $this->user = new PFUser(['language_id' => 'en']);

        $story_tracker  = aTracker()->withId(100)->build();
        $task_tracker   = aTracker()->withId(101)->build();
        $bug_tracker    = aTracker()->withId(102)->build();
        $dayoff_tracker = aTracker()->withId(103)->build();
        $story_tracker->setChildren(array($task_tracker, $bug_tracker));

        stub($tracker_factory)->getTrackerById(100)->returns($story_tracker);
        stub($tracker_factory)->getTrackerById(101)->returns($task_tracker);
        stub($tracker_factory)->getTrackerById(102)->returns($bug_tracker);
        stub($tracker_factory)->getTrackerById(103)->returns($dayoff_tracker);

        $this->collector     = new Tracker_XML_ChildrenCollector();
        $this->field         = aFileField()->withTracker($story_tracker)->withName('artifact links')->build();
        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter(
            $this->collector,
            $this->user
        );
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($this->changeset_value)->getField()->returns($this->field);
    }

    public function tearDown()
    {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function itExportsChildren()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCanView(111, 101, null),
            $this->anArtifactLinkInfoUserCanView(222, 102, null),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(false);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEqual((string) $field_change['field_name'], 'artifact links');
        $this->assertEqual((string) $field_change['type'], 'art_link');

        $this->assertEqual((int) $field_change->value[0], 111);
        $this->assertEqual((int) $field_change->value[1], 222);
    }

    public function itExportsChildrenNatureMode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
            $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEqual((string) $field_change['field_name'], 'artifact links');
        $this->assertEqual((string) $field_change['type'], 'art_link');

        $this->assertEqual((int) $field_change->value[0], 111);
        $this->assertEqual((int) $field_change->value[1], 222);
        $this->assertEqual(count($field_change->value), 2);
    }

    public function itDoesNotExportArtifactsThatAreNotChildren()
    {
        stub($this->changeset_value)->getArtifactIds()->returns(array(
            $this->anArtifactLinkInfoUserCanView(333, 103, null),
        ));

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual(count($field_change->value), 0);
    }

    public function itDoesNotExportChildrenUserCannotSee()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCannotView(111, 101, null),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(false);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEqual(count($field_change->value), 0);
    }

    public function itDoesNotExportChildrenUserCannotSeeNatureMode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCannotView(111, 101, '_is_child'),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;

        $this->assertEqual(count($field_change->value), 0);
    }

    public function itDoesNotFailIfNull()
    {
        stub($this->changeset_value)->getArtifactIds()->returns(null);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual(count($field_change->value), 0);
    }

    public function itCollectsChildren()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCanView(111, 101, null),
            $this->anArtifactLinkInfoUserCanView(222, 102, null),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(false);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $this->assertEqual($this->collector->getAllChildrenIds(), array(111, 222));
    }

    public function itCollectsChildrenNatureMode()
    {
        stub($this->changeset_value)->getValue()->returns(array(
            $this->anArtifactLinkInfoUserCanView(111, 101, '_is_child'),
            $this->anArtifactLinkInfoUserCanView(222, 102, '_is_child'),
        ));

        $artifact = mock('Tracker_Artifact');
        $tracker = mock('Tracker');
        stub($tracker)->isProjectAllowedToUseNature()->returns(true);
        stub($artifact)->getTracker()->returns($tracker);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $artifact,
            $this->changeset_value
        );

        $this->assertEqual($this->collector->getAllChildrenIds(), array(111, 222));
    }

    private function anArtifactLinkInfoUserCanView($artifact_id, $tracker_id, $nature)
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $nature);
        stub($artifact_link_info)->userCanView($this->user)->returns(true);

        return $artifact_link_info;
    }

    private function anArtifactLinkInfoUserCannotView($artifact_id, $tracker_id, $nature)
    {
        $artifact_link_info = $this->anArtifactLinkInfo($artifact_id, $tracker_id, $nature);
        stub($artifact_link_info)->userCanView($this->user)->returns(false);

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
