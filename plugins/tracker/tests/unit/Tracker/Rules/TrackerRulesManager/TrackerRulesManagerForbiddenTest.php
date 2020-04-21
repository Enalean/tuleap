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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Rule_List;
use Tracker_RuleFactory;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

class TrackerRulesManagerForbiddenTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

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
     * @var Mockery\MockInterface|Tracker_RuleFactory
     */
    private $rule_factory;
    /**
     * @var Mockery\MockInterface|TrackerRulesDateValidator
     */

    private $tracker_rules_date_validator;

    public function setUp(): void
    {
        $this->tracker = \Mockery::mock(\Tracker::class);

        $this->formelement_factory          = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->frozen_fields_dao            = \Mockery::mock(FrozenFieldsDao::class);
        $this->tracker_rules_list_validator = \Mockery::mock(TrackerRulesListValidator::class);
        $this->tracker_rules_date_validator = \Mockery::mock(TrackerRulesDateValidator::class);
        $this->tracker_factory              = \Mockery::mock(TrackerFactory::class);
        $this->rule_factory                 = \Mockery::mock(Tracker_RuleFactory::class);

        $this->tracker_rules_manager = \Mockery::mock(Tracker_RulesManager::class, [$this->tracker,
            $this->formelement_factory,
            $this->frozen_fields_dao,
            $this->tracker_rules_list_validator,
            $this->tracker_rules_date_validator,
            $this->tracker_factory])->makePartial();

        $this->frozen_fields_dao->shouldReceive('isFieldUsedInPostAction')->withArgs(['A'])->andReturn(false);
        $this->frozen_fields_dao->shouldReceive('isFieldUsedInPostAction')->withArgs(['B'])->andReturn(false);
        $this->frozen_fields_dao->shouldReceive('isFieldUsedInPostAction')->withArgs(['C'])->andReturn(false);
        $this->frozen_fields_dao->shouldReceive('isFieldUsedInPostAction')->withArgs(['D'])->andReturn(false);
        $this->frozen_fields_dao->shouldReceive('isFieldUsedInPostAction')->withArgs(['E'])->andReturn(false);

        $rule_list_1 = new Tracker_Rule_List(1, 1, 'A', '1', 'B', '2');
        $rule_list_2 = new Tracker_Rule_List(2, 1, 'B', '3', 'C', '4');
        $rule_list_3 = new Tracker_Rule_List(3, 1, 'D', '5', 'E', '6');

        $this->rule_factory->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([$rule_list_1, $rule_list_2, $rule_list_3]);

        $this->tracker_rules_manager->shouldReceive("getRuleFactory")->andReturn($this->rule_factory);
    }

    /**
     * @dataProvider forbiddenSourceProvider
     */
    public function testForbiddenSource($field_id, $source_id, $expected, $message)
    {
        $this->assertSame($expected, $this->tracker_rules_manager->fieldIsAForbiddenSource(1, $field_id, $source_id), $message);
    }

    public function forbiddenSourceProvider()
    {
        return [
            ['A', 'A', true, "Field A cannot be the source of field A"],
            ['B', 'A', true,"Field B cannot be the source of field A because A->B->A is cyclic"],
            ['C', 'A', true, "Field C cannot be the source of field A because A->B->C->A is cyclic"],
            ['D', 'A', false, "Field D can be the source of field A"],
            ['A', 'B', false,  "Field A is the source of field B"],
            ['B', 'B', true, "Field B cannot be the source of field B"],
            ['C', 'B', true,"Field C cannot be the source of field B because B is already a target"],
            ['D', 'B', true,"Field D cannot be the source of field B because B is already a target"],
            ['A', 'C', true, "Field A cannot be the source of field C because C is already a target"],
            ['B', 'C', false,  "Field B is the source of field C"],
            ['C', 'C', true, "Field C cannot be the source of field C"],
            ['D', 'C', true,"Field D cannot be the source of field C because C is already a target"],
            ['A', 'D', false, "Field A can be the source of field D"],
            ['B', 'D', false,  "Field B can be the source of field D"],
            ['C', 'D', false,  "Field C can be the source of field D"],
            ['D', 'D', true, "Field D cannot be the source of field D"]
        ];
    }

    /**
     * @dataProvider forbiddenTargetProvider
     */
    public function testForbiddenTarget($field_id, $source_id, $expected, $message)
    {
        $this->assertSame($expected, $this->tracker_rules_manager->fieldIsAForbiddenTarget(1, $field_id, $source_id), $message);
    }

    public function forbiddenTargetProvider()
    {
        return [
            ['A', 'A', true, "Field A cannot be the target of field A"],
            ['B', 'A', false, "Field B is the target of field A"],
            ['C', 'A', true, "Field C cannot be the target of field A because C is already a target"],
            ['D', 'A', false, "Field D can be the target of field A"],
            ['A', 'B', true, "Field A cannot be the target of field B because A->B->A is cyclic"],
            ['B', 'B', true, "Field B cannot be the target of field B"],
            ['C', 'B', false, "Field C is the target of field B"],
            ['D', 'B', false, "Field D can be the target of field B"],
            ['A', 'C', true, "Field A cannot be the target of field C because A->B->C->A is cyclic"],
            ['B', 'C', true, "Field B cannot be the target of field C because B is already a target"],
            ['C', 'C', true, "Field C cannot be the target of field C"],
            ['D', 'C', false, "Field D can be the target of field C"],
            ['A', 'D', false, "Field A can be the target of field D"],
            ['B', 'D', true, "Field B cannot be the target of field D because B is already a target"],
            ['C', 'D', true, "Field C cannot be the target of field D because C is already a target"],
            ['D', 'D', true, "Field D cannot be the target of field D"],
        ];
    }

    /**
     * @dataProvider fieldHasSourceProvider
     */
    public function testFieldHasSource($field_id, $expected)
    {
        $this->assertSame($expected, $this->tracker_rules_manager->fieldHasSource(1, $field_id));
    }

    public function fieldHasSourceProvider(): array
    {
        return [
            ['A', false],
            ['B', true],
            ['C', true],
            ['D', false],
            ['E', true],
            ['F', false]
        ];
    }

    /**
     * @dataProvider fieldHasTargetProvider
     */
    public function testFieldHasTarget($field_id, $expected)
    {
        $this->assertSame($expected, $this->tracker_rules_manager->fieldHasTarget(1, $field_id));
    }

    public function fieldHasTargetProvider(): array
    {
        return [
            ['A', true],
            ['B', true],
            ['C', false],
            ['D', true],
            ['E', false],
            ['F', false]
        ];
    }

    /**
     * @dataProvider isCyclicProvider
     */
    public function testIsCyclic($source_id, $target_id, $expected)
    {
        $this->assertsame($expected, $this->tracker_rules_manager->isCyclic(1, $source_id, $target_id));
    }

    public function isCyclicProvider(): array
    {
        return [
            ['A', 'A', true],
            ['A', 'B', false],
            ['A', 'C', false],
            ['A', 'D', false],
            ['A', 'E', false],
            ['B', 'A', true],
            ['B', 'B', true],
            ['B', 'C', false],
            ['B', 'D', false],
            ['B', 'E', false],
            ['C', 'A', true],
            ['C', 'B', true],
            ['C', 'C', true],
            ['C', 'D', false],
            ['C', 'E', false],
            ['D', 'A', false],
            ['D', 'B', false],
            ['D', 'C', false],
            ['D', 'D', true],
            ['D', 'E', false],
            ['E', 'A', false],
            ['E', 'B', false],
            ['E', 'C', false],
            ['E', 'D', true],
            ['E', 'E', true],
        ];
    }

    /**
     * @dataProvider ruleExistProvider
     */
    public function testRuleExists($source_id, $target_id, $expected)
    {
        $this->assertsame($expected, $this->tracker_rules_manager->ruleExists(1, $source_id, $target_id));
    }

    public function ruleExistProvider(): array
    {
        return [
            ['A', 'A', false],
            ['A', 'B', true],
            ['A', 'C', false],
            ['A', 'D', false],
            ['A', 'E', false],
            ['B', 'A', false],
            ['B', 'B', false],
            ['B', 'C', true],
            ['B', 'D', false],
            ['B', 'E', false],
            ['C', 'A', false],
            ['C', 'B', false],
            ['C', 'C', false],
            ['C', 'D', false],
            ['C', 'E', false],
            ['D', 'A', false],
            ['D', 'B', false],
            ['D', 'C', false],
            ['D', 'D', false],
            ['D', 'E', true],
            ['E', 'A', false],
            ['E', 'B', false],
            ['E', 'C', false],
            ['E', 'D', false],
            ['E', 'E', false],
        ];
    }

    public function testValueHasSourceTarget()
    {
        //value has source or target
        $this->assertTrue($this->tracker_rules_manager->valueHasSource(1, 'B', 2, 'A'));
        $this->assertFalse($this->tracker_rules_manager->valueHasSource(1, 'B', 2, 'C'));
        $this->assertFalse($this->tracker_rules_manager->valueHasSource(1, 'B', 3, 'C'));
        $this->assertTrue($this->tracker_rules_manager->valueHasTarget(1, 'B', 3, 'C'));
        $this->assertFalse($this->tracker_rules_manager->valueHasTarget(1, 'B', 3, 'A'));
        $this->assertFalse($this->tracker_rules_manager->valueHasTarget(1, 'B', 2, 'A'));
    }
}
