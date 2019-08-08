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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Rule_Date;
use Tracker_Rule_List;
use Tracker_RuleFactory;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

class TrackerRulesManagerValidationTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var Tracker_RulesManager
     */
    private $tracker_rules_manager;

    /**
     * @var Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Mockery\MockInterface|TrackerRulesListValidator
     */
    private $tracker_rules_list_validator;

    /**
     * @var Mockery\MockInterface|FrozenFieldsDao
     */
    private $frozen_fields_dao;

    /**
     * @var Mockery\MockInterface|\Tracker
     */
    private $tracker;

    /**
     * @var  Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\MockInterface|Tracker_Rule_Date
     */
    private $tracker_rule_date;

    /**
     * @var Mockery\MockInterface|Tracker_Rule_Date
     */
    private $tracker_rule_date2;

    public function setUp(): void
    {
        $this->tracker = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(10);

        $this->formelement_factory          = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->frozen_fields_dao            = \Mockery::mock(FrozenFieldsDao::class);
        $this->tracker_rules_list_validator = \Mockery::mock(TrackerRulesListValidator::class);
        $this->tracker_factory              = \Mockery::mock(TrackerFactory::class);

        $this->tracker_factory->shouldReceive("getTrackerById")->andReturn($this->tracker);

        $this->tracker_rules_manager = \Mockery::mock(Tracker_RulesManager::class, [$this->tracker,
            $this->formelement_factory,
            $this->frozen_fields_dao,
            $this->tracker_rules_list_validator,
            $this->tracker_factory])->makePartial();

        $this->tracker_rule_date  = \Mockery::mock(\Tracker_Rule_Date::class);
        $this->tracker_rule_date2 = \Mockery::mock(\Tracker_Rule_Date::class);

        $rule_list_1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $rule_list_2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $rule_list_3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $rule_factory                 = \Mockery::mock(Tracker_RuleFactory::class);
        $rule_factory->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([$rule_list_1, $rule_list_2, $rule_list_3]);

        $this->tracker_rules_manager->shouldReceive("getRuleFactory")->andReturn($rule_factory);
    }

    public function testValidateReturnsFalseWhenTheDateDataIsInvalid()
    {
        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => ''
        ];

        $this->tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(true);

        $source_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $source_field->shouldReceive('getLabel')->andReturn('aaaaa');
        $source_field->shouldReceive('setHasErrors')->withArgs([true]);
        $this->formelement_factory->shouldReceive('getFormElementById')->andReturn($source_field);

        $this->tracker_rule_date->shouldReceive('validate')->andReturn(true);
        $this->tracker_rule_date->shouldReceive('getSourceFieldId')->andReturn(10);
        $this->tracker_rule_date->shouldReceive('getTargetFieldId')->andReturn(11);

        $this->tracker_rule_date2->shouldReceive('validate')->andReturn(false);
        $this->tracker_rule_date2->shouldReceive('getSourceFieldId')->andReturn(12);
        $this->tracker_rule_date2->shouldReceive('getTargetFieldId')->andReturn(13);
        $this->tracker_rule_date2->shouldReceive('getComparator')->andReturn('<');

        $GLOBALS['Language']->shouldReceive('getText');
        $GLOBALS['Language']->shouldReceive('addFeedback');

        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturns([$this->tracker_rule_date, $this->tracker_rule_date2]);

        $this->assertFalse($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsTrueWhenThereAreValidDateRules()
    {
        $value_field_list = [
            10 => '',
            11 => '',
            12 => '',
            13 => ''
        ];

        $this->tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(true);

        $this->tracker_rule_date->shouldReceive('validate')->andReturn(true);
        $this->tracker_rule_date->shouldReceive('getSourceFieldId')->andReturn(10);
        $this->tracker_rule_date->shouldReceive('getTargetFieldId')->andReturn(11);

        $this->tracker_rule_date2->shouldReceive('validate')->andReturn(true);
        $this->tracker_rule_date2->shouldReceive('getSourceFieldId')->andReturn(12);
        $this->tracker_rule_date2->shouldReceive('getTargetFieldId')->andReturn(13);

        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturns([$this->tracker_rule_date, $this->tracker_rule_date2]);

        $this->assertTrue($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsTrueWhenThereAreNoRules()
    {
        $this->tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(true);
        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturns([]);

        $value_field_list = [];

        $this->assertTrue($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsTrueWhenValidateListRulesReturnsTrue()
    {
        $value_field_list = [
            123 => 456,
            789 => 586,
        ];

        $this->tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(true);

        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturns([]);

        $this->assertTrue($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }

    public function testValidateReturnsFalseWhenValidateListRulesReturnsFalse()
    {
        $value_field_list = [
            123 => 456,
            789 => 586,
        ];

        $this->tracker_rules_list_validator->shouldReceive('validateListRules')->andReturn(false);

        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturns([]);
        $this->tracker_rules_manager->setTrackerFormElementFactory($this->formelement_factory);

        $this->assertFalse($this->tracker_rules_manager->validate($this->tracker->getId(), $value_field_list));
    }
}
