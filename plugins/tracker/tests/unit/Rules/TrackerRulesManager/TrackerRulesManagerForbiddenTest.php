<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Rule;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_FormElementFactory;
use Tracker_Rule_List;
use Tracker_RuleFactory;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerRulesManagerForbiddenTest extends TestCase
{
    private Tracker_RulesManager&MockObject $tracker_rules_manager;

    private Tracker_FormElementFactory&MockObject $formelement_factory;

    private TrackerRulesListValidator&MockObject $tracker_rules_list_validator;

    private FrozenFieldsDao&MockObject $frozen_fields_dao;

    private Tracker $tracker;

    private TrackerFactory&MockObject $tracker_factory;

    private Tracker_RuleFactory&MockObject $rule_factory;

    private TrackerRulesDateValidator&MockObject $tracker_rules_date_validator;

    #[\Override]
    public function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->build();

        $this->formelement_factory          = $this->createMock(Tracker_FormElementFactory::class);
        $this->frozen_fields_dao            = $this->createMock(FrozenFieldsDao::class);
        $this->tracker_rules_list_validator = $this->createMock(TrackerRulesListValidator::class);
        $this->tracker_rules_date_validator = $this->createMock(TrackerRulesDateValidator::class);
        $this->tracker_factory              = $this->createMock(TrackerFactory::class);
        $this->rule_factory                 = $this->createMock(Tracker_RuleFactory::class);

        $this->tracker_rules_manager = $this->getMockBuilder(Tracker_RulesManager::class)
            ->onlyMethods(['getAllDateRulesByTrackerId'])
            ->setConstructorArgs([$this->tracker,
                $this->formelement_factory,
                $this->frozen_fields_dao,
                $this->tracker_rules_list_validator,
                $this->tracker_rules_date_validator,
                $this->tracker_factory,
                new NullLogger(),
                $this->createMock(\Tracker_Rule_List_Factory::class),
                $this->createMock(\Tracker_Rule_Date_Factory::class),
                $this->rule_factory,
            ])->getMock();

        $this->frozen_fields_dao->method('isFieldUsedInPostAction')->willReturnMap([
            [1, false],
            [2, false],
            [3, false],
            [4, false],
            [5, false],
        ]);

        $rule_list_1 = new Tracker_Rule_List(1, 1, 1, 1, 2, 2);
        $rule_list_2 = new Tracker_Rule_List(2, 1, 2, 3, 3, 4);
        $rule_list_3 = new Tracker_Rule_List(3, 1, 4, 5, 5, 6);

        $this->rule_factory->method('getAllListRulesByTrackerWithOrder')->willReturn([$rule_list_1, $rule_list_2, $rule_list_3]);

        $involved_fields_1 = new InvolvedFieldsInRule(1, 2);
        $involved_fields_2 = new InvolvedFieldsInRule(2, 3);
        $involved_fields_3 = new InvolvedFieldsInRule(4, 5);

        $this->rule_factory->method('getInvolvedFieldsByTrackerIdCollection')->willReturn([$involved_fields_1, $involved_fields_2, $involved_fields_3]);
    }

    #[DataProvider('forbiddenSourceProvider')]
    public function testForbiddenSource($field_id, $source_id, $expected, $message)
    {
        self::assertSame($expected, $this->tracker_rules_manager->fieldIsAForbiddenSource(1, $field_id, $source_id), $message);
    }

    public static function forbiddenSourceProvider()
    {
        return [
            [1, 1, true, 'Field 1 cannot be the source of field 1'],
            [2, 1, true,'Field 2 cannot be the source of field 1 because 1->2->1 is cyclic'],
            [3, 1, true, 'Field 3 cannot be the source of field 1 because 1->2->3->1 is cyclic'],
            [4, 1, false, 'Field 4 can be the source of field 1'],
            [1, 2, false,  'Field 1 is the source of field 2'],
            [2, 2, true, 'Field 2 cannot be the source of field 2'],
            [3, 2, true,'Field 3 cannot be the source of field 2 because 2 is already a target'],
            [4, 2, true,'Field 4 cannot be the source of field 2 because 2 is already a target'],
            [1, 3, true, 'Field 1 cannot be the source of field 3 because 3 is already a target'],
            [2, 3, false,  'Field 2 is the source of field 3'],
            [3, 3, true, 'Field 3 cannot be the source of field 3'],
            [4, 3, true,'Field 4 cannot be the source of field 3 because 3 is already a target'],
            [1, 4, false, 'Field 1 can be the source of field 4'],
            [2, 4, false,  'Field 2 can be the source of field 4'],
            [3, 4, false,  'Field 3 can be the source of field 4'],
            [4, 4, true, 'Field 4 cannot be the source of field 4'],
        ];
    }

    #[DataProvider('forbiddenTargetProvider')]
    public function testForbiddenTarget($field_id, $source_id, $expected, $message)
    {
        self::assertSame($expected, $this->tracker_rules_manager->fieldIsAForbiddenTarget(1, $field_id, $source_id), $message);
    }

    public static function forbiddenTargetProvider()
    {
        return [
            [1, 1, true, 'Field 1 cannot be the target of field 1'],
            [2, 1, false, 'Field 2 is the target of field 1'],
            [3, 1, true, 'Field 3 cannot be the target of field 1 because 3 is already a target'],
            [4, 1, false, 'Field 4 can be the target of field 1'],
            [1, 2, true, 'Field 1 cannot be the target of field 2 because 1->2->1 is cyclic'],
            [2, 2, true, 'Field 2 cannot be the target of field 2'],
            [3, 2, false, 'Field 3 is the target of field 2'],
            [4, 2, false, 'Field 4 can be the target of field 2'],
            [1, 3, true, 'Field 1 cannot be the target of field 3 because 1->2->3->1 is cyclic'],
            [2, 3, true, 'Field 2 cannot be the target of field 3 because 2 is already a target'],
            [3, 3, true, 'Field 3 cannot be the target of field 3'],
            [4, 3, false, 'Field 4 can be the target of field 3'],
            [1, 4, false, 'Field 1 can be the target of field 4'],
            [2, 4, true, 'Field 2 cannot be the target of field 4 because 2 is already a target'],
            [3, 4, true, 'Field 3 cannot be the target of field 4 because 3 is already a target'],
            [4, 4, true, 'Field 4 cannot be the target of field 4'],
        ];
    }

    #[DataProvider('fieldHasSourceProvider')]
    public function testFieldHasSource($field_id, $expected)
    {
        self::assertSame($expected, $this->tracker_rules_manager->fieldHasSource(1, $field_id));
    }

    public static function fieldHasSourceProvider(): array
    {
        return [
            [1, false],
            [2, true],
            [3, true],
            [4, false],
            [5, true],
            [6, false],
        ];
    }

    #[DataProvider('fieldHasTargetProvider')]
    public function testFieldHasTarget($field_id, $expected)
    {
        self::assertSame($expected, $this->tracker_rules_manager->fieldHasTarget(1, $field_id));
    }

    public static function fieldHasTargetProvider(): array
    {
        return [
            [1, true],
            [2, true],
            [3, false],
            [4, true],
            [5, false],
            [6, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isCyclicProvider')]
    public function testcheckIfRuleIsCyclic($source_id, $target_id, $expected)
    {
        $this->assertsame($expected, $this->tracker_rules_manager->checkIfRuleIsCyclic(1, $source_id, $target_id));
    }

    public static function isCyclicProvider(): array
    {
        return [
            [1, 1, true],
            [1, 2, false],
            [1, 3, false],
            [1, 4, false],
            [1, 5, false],
            [2, 1, true],
            [2, 2, true],
            [2, 3, false],
            [2, 4, false],
            [2, 5, false],
            [3, 1, true],
            [3, 2, true],
            [3, 3, true],
            [3, 4, false],
            [3, 5, false],
            [4, 1, false],
            [4, 2, false],
            [4, 3, false],
            [4, 4, true],
            [4, 5, false],
            [5, 1, false],
            [5, 2, false],
            [5, 3, false],
            [5, 4, true],
            [5, 5, true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ruleExistProvider')]
    public function testRuleExists($source_id, $target_id, $expected)
    {
        $this->assertsame($expected, $this->tracker_rules_manager->ruleExists(1, $source_id, $target_id));
    }

    public static function ruleExistProvider(): array
    {
        return [
            [1, 1, false],
            [1, 2, true],
            [1, 3, false],
            [1, 4, false],
            [1, 5, false],
            [2, 1, false],
            [2, 2, false],
            [2, 3, true],
            [2, 4, false],
            [2, 5, false],
            [3, 1, false],
            [3, 2, false],
            [3, 3, false],
            [3, 4, false],
            [3, 5, false],
            [4, 1, false],
            [4, 2, false],
            [4, 3, false],
            [4, 4, false],
            [4, 5, true],
            [5, 1, false],
            [5, 2, false],
            [5, 3, false],
            [5, 4, false],
            [5, 5, false],
        ];
    }

    public function testValueHasSourceTarget()
    {
        //value has source or target
        $this->assertTrue($this->tracker_rules_manager->valueHasSource(1, 2, 2, 1));
        $this->assertFalse($this->tracker_rules_manager->valueHasSource(1, 2, 2, 3));
        $this->assertFalse($this->tracker_rules_manager->valueHasSource(1, 2, 3, 3));
        $this->assertTrue($this->tracker_rules_manager->valueHasTarget(1, 2, 3, 3));
        $this->assertFalse($this->tracker_rules_manager->valueHasTarget(1, 2, 3, 1));
        $this->assertFalse($this->tracker_rules_manager->valueHasTarget(1, 2, 2, 1));
    }
}
