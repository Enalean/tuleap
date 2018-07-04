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

namespace Tuleap\Tracker\XML\Updater;

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticTargetField;
use Tuleap\Tracker\Events\MoveArtifactParseFieldChangeNodes;

require_once __DIR__.'/../../../bootstrap.php';

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

        $this->user          = Mockery::mock(PFUser::class);
        $this->tracker       = Mockery::mock(Tracker::class);
        $this->event_manager = Mockery::mock(EventManager::class);

        $this->updater = new MoveChangesetXMLUpdater($this->event_manager);

        $this->user->shouldReceive('getId')->andReturn(101);
    }

    public function testItUpdatesTheTitleFieldChangeTagsInMoveAction()
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
        $target_tracker->shouldReceive('getTitleField')->andReturn($target_title_field);
        $target_tracker->shouldReceive('getDescriptionField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);
        $target_title_field->shouldReceive('getName')->andReturn('title2');
        $source_title_field->shouldReceive('getName')->andReturn('summary');
        $source_title_field->shouldReceive('getId')->andReturn(1001);

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticTargetField $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time = time();
        $this->updater->update($this->tracker, $target_tracker, $artifact_xml, $this->user, $time);

        $this->assertEquals((int)$artifact_xml['tracker_id'], 201);
        $this->assertEquals((string)$artifact_xml->changeset->submitted_on, date('c', $time));
        $this->assertEquals((int)$artifact_xml->changeset->submitted_by, $this->user->getId());

        $this->assertEquals(count($artifact_xml->changeset->field_change), 1);
        $this->assertEquals($artifact_xml->changeset->field_change[0]['field_name'], 'title2');
        $this->assertEquals((string)$artifact_xml->changeset->field_change[0]->value, 'Initial summary value');
    }

    public function testItUpdatesTheDescriptionFieldChangeTags()
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
            . '  </changeset>'
            .'  <changeset>'
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
        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getId')->andReturn(201);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticTargetField $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time = time();
        $this->updater->update($this->tracker, $target_tracker, $artifact_xml, $this->user, $time);

        $this->assertEquals((int)$artifact_xml['tracker_id'], 201);
        $this->assertEquals((string)$artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int)$artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 2);
        $this->assertEquals((string)$artifact_xml->changeset[0]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string)$artifact_xml->changeset[0]->field_change[0]->value, '<p><strong>Description</strong></p>');
        $this->assertEquals((string)$artifact_xml->changeset[0]->field_change[0]->value['format'], 'html');
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]->value, '<p><strong>Description v2</strong></p>');
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]->value['format'], 'html');
    }

    public function testItDealsWithCommentTagsInMoveAction()
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
            .'  <changeset>'
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
        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);
        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticTargetField $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $this->updater->update($this->tracker, $target_tracker, $artifact_xml, $this->user, time());

        $this->assertEquals(count($artifact_xml->changeset), 2);
        $this->assertNull($artifact_xml->changeset[0]->comments[0]);
        $this->assertEquals((string)$artifact_xml->changeset[1]->comments->comment[0]->body, 'My comment');
    }

    public function testItDoesNotRemoveFirstChangesetTagInMoveAction()
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
            .'  <changeset>'
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
        $target_tracker->shouldReceive('getDescriptionField')->andReturn($target_description_field);
        $target_tracker->shouldReceive('getTitleField')->andReturn(null);
        $target_tracker->shouldReceive('getId')->andReturn(201);
        $target_description_field->shouldReceive('getName')->andReturn('v2desc');
        $source_description_field->shouldReceive('getName')->andReturn('desc');

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticTargetField $event) {
            return true;
        }));
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            return true;
        }));

        $time = time();
        $this->updater->update($this->tracker, $target_tracker, $artifact_xml, $this->user, $time);

        $this->assertEquals((int)$artifact_xml['tracker_id'], 201);
        $this->assertEquals((string)$artifact_xml->changeset[0]->submitted_on, date('c', $time));
        $this->assertEquals((int)$artifact_xml->changeset[0]->submitted_by, 101);

        $this->assertEquals(count($artifact_xml->changeset), 3);
        $this->assertNull($artifact_xml->changeset[0]->field_change[0]);
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]->value, '<p><strong>Description</strong></p>');
        $this->assertEquals((string)$artifact_xml->changeset[1]->field_change[0]->value['format'], 'html');
        $this->assertEquals((string)$artifact_xml->changeset[2]->field_change[0]['field_name'], 'v2desc');
        $this->assertEquals((string)$artifact_xml->changeset[2]->field_change[0]->value, '<p><strong>Description v2</strong></p>');
        $this->assertEquals((string)$artifact_xml->changeset[2]->field_change[0]->value['format'], 'html');
    }

    public function testItAsksForExternalPluginsIfThereIsAnExternalFieldSemantic()
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

        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticTargetField $event) {
            $target_external_semantic_field = Mockery::mock(Tracker_FormElement_Field::class);
            $event->setField($target_external_semantic_field);
            return true;
        }))->once();
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactParseFieldChangeNodes $event) {
            $event->setModifiedByPlugin();
            return true;
        }))->once();

        $time = time();
        $this->updater->update($this->tracker, $target_tracker, $artifact_xml, $this->user, $time);
    }
}
