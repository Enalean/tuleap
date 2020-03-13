<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

class Tracker_Workflow_Trigger_RulesBuilderDataTest extends TuleapTestCase
{

    public function itHasNoData()
    {
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), array());
        $this->assertEqual(
            $rules_builder_data->fetchFormattedForJson(),
            array(
                "targets" => array(),
                "conditions" => array(
                    array(
                        "name" => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
                        "operator" => "or"
                    ),
                    array(
                        "name" => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
                        "operator" => "and"
                    ),
                ),
                "triggers" => array(),
            )
        );
    }

    public function itHasATargetFieldOfTheTrackerOnWhichRulesWillApply()
    {
        $field_id = 269;
        $target_field = aMockField()->withId($field_id)->build();
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(array($target_field)), array());

        stub($target_field)->fetchFormattedForJson()->returns('whatever')->once();

        $result = $rules_builder_data->fetchFormattedForJson();
        $this->assertCount($result['targets'], 1);
        $this->assertEqual($result['targets'][$field_id], 'whatever');
    }

    public function itHasATriggerTracker()
    {
        $tracker_id = 90;
        $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            aTracker()->withId($tracker_id)->withName('Tasks')->build(),
            new ArrayIterator()
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), array($triggering_field));
        $result = $rules_builder_data->fetchFormattedForJson();
        $this->assertCount($result['triggers'], 1);
        $this->assertEqual($result['triggers'][$tracker_id]['id'], 90);
        $this->assertEqual($result['triggers'][$tracker_id]['name'], 'Tasks');
        $this->assertEqual($result['triggers'][$tracker_id]['fields'], array());
    }

    public function itHasATriggerTrackerWithAField()
    {
        $field_id = 693;
        $field = aMockField()->withId($field_id)->build();
        stub($field)->fetchFormattedForJson()->returns('whatever')->once();

        $tracker_id = 90;
        $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            aTracker()->withId($tracker_id)->withName('Tasks')->build(),
            new ArrayIterator(array($field))
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), array($triggering_field));
        $result = $rules_builder_data->fetchFormattedForJson();
        $trigger = $result['triggers'][$tracker_id];
        $this->assertCount($trigger['fields'], 1);
        $this->assertEqual($trigger['fields'][$field_id], 'whatever');
    }
}
