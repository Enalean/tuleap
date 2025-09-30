<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesBuilderDataTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function testItHasNoData(): void
    {
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), []);
        $this->assertEquals(
            [
                'targets' => [],
                'conditions' => [
                    [
                        'name' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
                        'operator' => 'or',
                    ],
                    [
                        'name' => Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_ALL_OFF,
                        'operator' => 'and',
                    ],
                ],
                'triggers' => [],
            ],
            $rules_builder_data->fetchFormattedForJson()
        );
    }

    public function testItHasATargetFieldOfTheTrackerOnWhichRulesWillApply(): void
    {
        $field_id     = 269;
        $target_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $target_field->method('getId')->willReturn($field_id);
        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator([$target_field]), []);

        $target_field->expects($this->once())->method('fetchFormattedForJson')->willReturn('whatever');

        $result = $rules_builder_data->fetchFormattedForJson();
        $this->assertCount(1, $result['targets']);
        $this->assertEquals('whatever', $result['targets'][$field_id]);
    }

    public function testItHasATriggerTracker(): void
    {
        $tracker_id = 90;
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->withName('Tasks')->build();

        $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            $tracker,
            new ArrayIterator()
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), [$triggering_field]);
        $result             = $rules_builder_data->fetchFormattedForJson();
        $this->assertCount(1, $result['triggers']);
        $this->assertEquals(90, $result['triggers'][$tracker_id]['id']);
        $this->assertEquals('Tasks', $result['triggers'][$tracker_id]['name']);
        $this->assertEquals([], $result['triggers'][$tracker_id]['fields']);
    }

    public function testItHasATriggerTrackerWithAField(): void
    {
        $field_id = 693;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $field->method('getId')->willReturn($field_id);
        $field->expects($this->once())->method('fetchFormattedForJson')->willReturn('whatever');

        $tracker_id = 90;
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->withName('Tasks')->build();

        $triggering_field = new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            $tracker,
            new ArrayIterator([$field])
        );

        $rules_builder_data = new Tracker_Workflow_Trigger_RulesBuilderData(new ArrayIterator(), [$triggering_field]);
        $result             = $rules_builder_data->fetchFormattedForJson();
        $trigger            = $result['triggers'][$tracker_id];
        $this->assertCount(1, $trigger['fields']);
        $this->assertEquals('whatever', $trigger['fields'][$field_id]);
    }
}
