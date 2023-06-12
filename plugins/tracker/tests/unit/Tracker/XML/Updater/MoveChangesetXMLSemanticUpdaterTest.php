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

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\Move\NoFeedbackFieldCollector;
use Tuleap\Tracker\Action\MoveContributorSemanticChecker;
use Tuleap\Tracker\Action\MoveDescriptionSemanticChecker;
use Tuleap\Tracker\Action\MoveSemanticChecker;
use Tuleap\Tracker\Action\MoveStatusSemanticChecker;
use Tuleap\Tracker\Action\MoveTitleSemanticChecker;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
use Tuleap\Tracker\Events\MoveArtifactParseFieldChangeNodes;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;
use Tuleap\Tracker\Test\Stub\UpdateBindValueForSemanticStub;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class MoveChangesetXMLSemanticUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MoveChangesetXMLSemanticUpdater $updater;
    private PFUser $user;
    private PFUser $submitter;
    private Tracker&Mockery\MockInterface $tracker;
    private EventManager&Mockery\MockInterface $event_manager;
    private FieldValueMatcher&Mockery\MockInterface $value_matcher;
    private MoveTitleSemanticChecker&Mockery\MockInterface $title_semantic_checker;
    private MoveDescriptionSemanticChecker&Mockery\MockInterface $description_semantic_checker;
    private MoveStatusSemanticChecker&Mockery\MockInterface $status_semantic_checker;
    private MoveContributorSemanticChecker&Mockery\MockInterface $contributor_semantic_checker;
    private MoveChangesetXMLUpdater $move_changeset_updater;
    private NoFeedbackFieldCollector $collector;
    private UpdateBindValueForSemanticStub $bind_value_for_semantic_updater;

    public function setUp(): void
    {
        parent::setUp();

        $project                               = ProjectTestBuilder::aProject()->withPublicName('Project01')->build();
        $this->user                            = UserTestBuilder::anActiveUser()->withId(102)->build();
        $this->submitter                       = UserTestBuilder::anActiveUser()->withId(101)->build();
        $this->tracker                         = Mockery::mock(Tracker::class);
        $this->event_manager                   = Mockery::mock(EventManager::class);
        $this->value_matcher                   = Mockery::mock(FieldValueMatcher::class);
        $this->title_semantic_checker          = Mockery::mock(MoveTitleSemanticChecker::class);
        $this->description_semantic_checker    = Mockery::mock(MoveDescriptionSemanticChecker::class);
        $this->status_semantic_checker         = Mockery::mock(MoveStatusSemanticChecker::class);
        $this->contributor_semantic_checker    = Mockery::mock(MoveContributorSemanticChecker::class);
        $this->move_changeset_updater          = new MoveChangesetXMLUpdater();
        $this->bind_value_for_semantic_updater = UpdateBindValueForSemanticStub::build();

        $this->updater = new MoveChangesetXMLSemanticUpdater(
            $this->move_changeset_updater,
            $this->title_semantic_checker,
            $this->description_semantic_checker,
            $this->status_semantic_checker,
            $this->contributor_semantic_checker,
            $this->event_manager,
            $this->bind_value_for_semantic_updater,
            $this->value_matcher
        );

        $this->collector = new NoFeedbackFieldCollector();

        $this->tracker->allows()->getName()->andReturn('TrackerName');
        $this->tracker->allows()->getProject()->andReturn($project);
    }

    public function testItUpdatesTheTitleFieldChangeTagsInMoveAction(): void
    {
        $source_title_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_title_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_tracker     = Mockery::mock(Tracker::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
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

        $this->tracker->shouldReceive('getTitleField')->andReturn($source_title_field);
        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_tracker->shouldReceive('getTitleField')->andReturn($target_title_field);
        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $target_title_field->shouldReceive('getName')->andReturn('title2');
        $source_title_field->shouldReceive('getName')->andReturn('summary');
        $source_title_field->shouldReceive('getId')->andReturn(1001);

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals((int) $artifact_xml['tracker_id'], 201);
        $this->assertEquals((string) $artifact_xml->changeset->submitted_on, date('c', $time));
        $this->assertEquals((int) $artifact_xml->changeset->submitted_by, $this->submitter->getId());

        $this->assertEquals(count($artifact_xml->changeset->field_change), 1);
        $this->assertEquals($artifact_xml->changeset->field_change[0]['field_name'], 'title2');
        $this->assertEquals((string) $artifact_xml->changeset->field_change[0]->value, 'Initial summary value');
    }

    public function testItUpdatesTheDescriptionFieldChangeTags(): void
    {
        $source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_description_field = Mockery::mock(Tracker_FormElement_Field::class);

        $target_tracker = Mockery::mock(Tracker::class);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2015</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Second summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description v2</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details v2</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '</artifact>');

        $this->tracker->shouldReceive('getDescriptionField')->andReturn($source_description_field);
        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals((int) $artifact_xml['tracker_id'], 201);
        $this->assertEquals((string) $artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int) $artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 3);
        $this->assertEquals((string) $artifact_xml->changeset[0]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string) $artifact_xml->changeset[0]->field_change[0]->value, '<p><strong>Description</strong></p>');
        $this->assertEquals((string) $artifact_xml->changeset[0]->field_change[0]->value['format'], 'html');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]->value, '<p><strong>Description v2</strong></p>');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]->value['format'], 'html');
    }

    public function testItUpdatesTheStatusFieldChange(): void
    {
        $target_tracker = Mockery::mock(Tracker::class);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $source_status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $target_status_field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="status">'
            . '      <value format="id">101</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2015</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Second summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description v2</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="status">'
            . '      <value format="id">105</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '</artifact>');

        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn($target_status_field);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->tracker->shouldReceive('getStatusField')->andReturn($source_status_field);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $source_status_field->shouldReceive('getName')->andReturn('status');
        $target_status_field->shouldReceive('getName')->andReturn('V2status');

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->value_matcher
            ->shouldReceive('getMatchingValueByDuckTyping')
            ->with($source_status_field, $target_status_field, 101)
            ->andReturn(201);

        $this->value_matcher
            ->shouldReceive('getMatchingValueByDuckTyping')
            ->with($source_status_field, $target_status_field, 105)
            ->andReturn(205);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals((int) $artifact_xml['tracker_id'], 201);
        $this->assertEquals((string) $artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int) $artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 3);
        $this->assertEquals((string) $artifact_xml->changeset[0]->field_change[0]['field_name'], 'V2status');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]['field_name'], 'V2status');
        $this->assertSame(2, $this->bind_value_for_semantic_updater->getNbValuesUpdated());
    }

    public function testItUpdatesTheContributorFieldChange(): void
    {
        $target_tracker = Mockery::mock(Tracker::class);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $source_contributor_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $target_contributor_field = Mockery::mock(Tracker_FormElement_Field_List::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="assigned_to" type="list" bind="users">'
            . '      <value format="ldap">101</value>'
            . '      <value format="ldap">102</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2015</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Second summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description v2</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="assigned_to" type="list" bind="users">'
            . '      <value format="ldap">105</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '</artifact>');

        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn($target_contributor_field);

        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn($source_contributor_field);

        $source_contributor_field->shouldReceive('getName')->andReturn('assigned_to');
        $target_contributor_field->shouldReceive('getName')->andReturn('contrib');

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->value_matcher
            ->shouldReceive('isSourceUserValueMatchingATargetUserValue')
            ->andReturn(true);

        $this->value_matcher
            ->shouldReceive('isSourceUserValueMatchingATargetUserValue')
            ->andReturn(false);

        $this->value_matcher
            ->shouldReceive('isSourceUserValueMatchingATargetUserValue')
            ->andReturn(true);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals((int) $artifact_xml['tracker_id'], 201);
        $this->assertEquals((string) $artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int) $artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 3);
        $this->assertEquals((string) $artifact_xml->changeset[0]->field_change[0]['field_name'], 'contrib');
        $this->assertEquals((int) $artifact_xml->changeset[0]->field_change[0]->value, 101);
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]['field_name'], 'contrib');
        $this->assertEquals((int) $artifact_xml->changeset[1]->field_change[0]->value, 105);
    }

    public function testItDealsWithCommentTagsInMoveAction(): void
    {
        $source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_tracker           = Mockery::mock(Tracker::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details</value>'
            . '    </field_change>'
            . '    <comments/>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2015</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Second summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description v2</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details v2</value>'
            . '    </field_change>'
            . '    <comments>
                        <comment>
                            <submitted_by format="id">123</submitted_by>
                            <submitted_on format="ISO8601">2014</submitted_on>
                            <body format="text"><![CDATA[My comment]]></body>
                        </comment>
                    </comments>'
            . '  </changeset>'
            . '</artifact>');

        $this->tracker->shouldReceive('getDescriptionField')->andReturn($source_description_field);
        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals(count($artifact_xml->changeset), 3);
        $this->assertNull($artifact_xml->changeset[0]->comments[0]);
        $this->assertEquals((string) $artifact_xml->changeset[1]->comments->comment[0]->body, 'My comment');
    }

    public function testItDoesNotRemoveFirstChangesetTagInMoveAction(): void
    {
        $source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_tracker           = Mockery::mock(Tracker::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2013</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Initial summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details v2</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '  <changeset>'
            . '    <submitted_on>2015</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="summary">'
            . '      <value>Second summary value</value>'
            . '    </field_change>'
            . '    <field_change field_name="desc">'
            . '      <value format="html"><![CDATA[<p><strong>Description v2</strong></p>]]></value>'
            . '    </field_change>'
            . '    <field_change field_name="details">'
            . '      <value>Content of details v3</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '</artifact>');

        $this->tracker->shouldReceive('getDescriptionField')->andReturn($source_description_field);
        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals((int) $artifact_xml['tracker_id'], 201);
        $this->assertEquals((string) $artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int) $artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 4);
        $this->assertNull($artifact_xml->changeset[0]->field_change[0]);
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]->value, '<p><strong>Description</strong></p>');
        $this->assertEquals((string) $artifact_xml->changeset[1]->field_change[0]->value['format'], 'html');
        $this->assertEquals((string) $artifact_xml->changeset[2]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string) $artifact_xml->changeset[2]->field_change[0]->value, '<p><strong>Description v2</strong></p>');
        $this->assertEquals((string) $artifact_xml->changeset[2]->field_change[0]->value['format'], 'html');
    }

    public function testItAsksForExternalPluginsIfThereIsAnExternalFieldSemantic(): void
    {
        $target_tracker = Mockery::mock(Tracker::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<artifact>'
            . '  <changeset>'
            . '    <submitted_on>2014</submitted_on>'
            . '    <submitted_by>123</submitted_by>'
            . '    <field_change field_name="effort">'
            . '      <value>125</value>'
            . '    </field_change>'
            . '  </changeset>'
            . '</artifact>');

        $target_tracker->shouldReceive('getId')->andReturn(201);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            $checker = Mockery::mock(MoveSemanticChecker::class);
            $checker->shouldReceive([
                'areBothSemanticsDefined' => true,
                'doesBothSemanticFieldHaveTheSameType' => true,
                'areSemanticsAligned' => true,
                'getSemanticName' => 'whatever',
            ]);

            $event->addExternalSemanticsChecker($checker);
            return true;
        }))->once();
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            $event->setModifiedByPlugin();
            return true;
        }))->once();

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );
    }

    public function testItAddsALastChangesetWithACommentToSayThatThisArtifactHasBeenMoved(): void
    {
        $source_title_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_title_field = Mockery::mock(Tracker_FormElement_Field::class);
        $target_tracker     = Mockery::mock(Tracker::class);

        $artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
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

        $this->tracker->shouldReceive('getTitleField')->andReturn($source_title_field);
        $this->tracker->shouldReceive('getStatusField')->andReturn(null);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $target_tracker->shouldReceive('getTitleField')->andReturn($target_title_field);
        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getStatusField')->andReturn(null);
        $target_tracker->shouldReceive('getContributorField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);

        $target_title_field->shouldReceive('getName')->andReturn('title2');
        $source_title_field->shouldReceive('getName')->andReturn('summary');
        $source_title_field->shouldReceive('getId')->andReturn(1001);

        $this->title_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(true);

        $this->description_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->contributor_semantic_checker
            ->shouldReceive('areSemanticsAligned')
            ->with($this->tracker, $target_tracker)
            ->andReturns(false);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time       = time();
        $moved_time = time();
        $this->updater->update(
            $this->user,
            $this->tracker,
            $target_tracker,
            $artifact_xml,
            $this->submitter,
            $time,
            $moved_time,
            $this->collector
        );

        $this->assertEquals(count($artifact_xml->changeset), 2);

        $this->assertEquals((string) $artifact_xml->changeset[1]->submitted_on, date('c', $moved_time));
        $this->assertEquals((int) $artifact_xml->changeset[1]->submitted_by, $this->user->getId());

        $this->assertEquals((string) $artifact_xml->changeset[1]->comments->comment->submitted_on, date('c', $moved_time));
        $this->assertEquals((string) $artifact_xml->changeset[1]->comments->comment->submitted_by, $this->user->getId());
        $this->assertEquals((string) $artifact_xml->changeset[1]->comments->comment->body['format'], 'text');
        $this->assertNotEmpty((string) $artifact_xml->changeset[1]->comments->comment->body);

        $this->assertEquals(count($artifact_xml->changeset[1]->field_change), 0);
    }
}
