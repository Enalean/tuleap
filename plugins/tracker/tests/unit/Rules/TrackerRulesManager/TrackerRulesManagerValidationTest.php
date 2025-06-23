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

declare(strict_types=1);

namespace Tuleap\Tracker\Rule;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker_FormElementFactory;
use Tracker_Rule_Date;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List;
use Tracker_Rule_List_Factory;
use Tracker_RuleFactory;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TrackerRulesManagerValidationTest extends TestCase
{
    use GlobalResponseMock;

    private Tracker_RulesManager&MockObject $tracker_rules_manager;

    private TrackerRulesListValidator&MockObject $tracker_rules_list_validator;

    private Tracker $tracker;

    private TrackerRulesDateValidator&MockObject $tracker_rules_date_validator;

    public function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();

        $formelement_factory                = $this->createMock(Tracker_FormElementFactory::class);
        $frozen_fields_dao                  = $this->createMock(FrozenFieldsDao::class);
        $this->tracker_rules_list_validator = $this->createMock(TrackerRulesListValidator::class);
        $this->tracker_rules_date_validator = $this->createMock(TrackerRulesDateValidator::class);
        $tracker_factory                    = $this->createMock(TrackerFactory::class);

        $tracker_factory->method('getTrackerById')->willReturn($this->tracker);

        $this->tracker_rules_manager = $this->getMockBuilder(Tracker_RulesManager::class)
            ->onlyMethods(['getAllListRulesByTrackerWithOrder', 'getAllDateRulesByTrackerId'])
            ->setConstructorArgs([$this->tracker,
                $formelement_factory,
                $frozen_fields_dao,
                $this->tracker_rules_list_validator,
                $this->tracker_rules_date_validator,
                $tracker_factory,
                new NullLogger(),
                $this->createMock(Tracker_Rule_List_Factory::class),
                $this->createMock(Tracker_Rule_Date_Factory::class),
                $this->createMock(Tracker_RuleFactory::class),
            ])->getMock();

        $tracker_rule_date  = $this->createMock(Tracker_Rule_Date::class);
        $tracker_rule_date2 = $this->createMock(Tracker_Rule_Date::class);

        $rule_list_1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $rule_list_2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $rule_list_3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $this->tracker_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn([$rule_list_1, $rule_list_2, $rule_list_3]);

        $this->tracker_rules_manager->method('getAllDateRulesByTrackerId')->willReturn([$tracker_rule_date, $tracker_rule_date2]);
    }

    public function testValidateReturnsFalseWhenTheDateDataIsInvalid(): void
    {
        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        ];

        $this->tracker_rules_list_validator->method('validateListRules')->willReturn(true);
        $this->tracker_rules_date_validator->method('validateDateRules')->willReturn(false);

        $this->assertFalse($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsTrueWhenThereAreValidDateRules(): void
    {
        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => '',
        ];

        $this->tracker_rules_list_validator->method('validateListRules')->willReturn(true);
        $this->tracker_rules_date_validator->method('validateDateRules')->willReturn(true);

        $this->assertTrue($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsFalseWhenValidateListRulesReturnsFalse(): void
    {
        $value_field_list = [
            123 => 456,
            789 => 586,
        ];

        $this->tracker_rules_list_validator->method('validateListRules')->willReturn(false);
        $this->tracker_rules_date_validator->method('validateDateRules')->willReturn(true);

        $this->assertFalse($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }
}
