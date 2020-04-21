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
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Rule_Date;
use Tracker_Rule_List;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

class TrackerRulesManagerIsUsedInFieldDependencyTest extends TestCase
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
     * @var  Mockery\MockInterface|\Tracker_FormElement_Field_Selectbox
     */
    private $source_field_list;

    /**
     * @var  Mockery\MockInterface|\Tracker_FormElement_Field_Selectbox
     */
    private $target_field_list;

    /**
     * @var  Mockery\MockInterface|\Tracker_FormElement_Field_Selectbox
     */
    private $a_field_not_used_in_rules;

    /**
     * @var  Mockery\MockInterface|\Tracker_FormElement_Field_Date
     */
    private $source_field_date;

    /**
     * @var  Mockery\MockInterface|\Tracker_FormElement_Field_Date
     */
    private $target_field_date;

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

        $this->tracker_rules_manager = \Mockery::mock(Tracker_RulesManager::class, [$this->tracker,
            $this->formelement_factory,
            $this->frozen_fields_dao,
            $this->tracker_rules_list_validator,
            $this->tracker_rules_date_validator,
            $this->tracker_factory])->makePartial();

        $this->a_field_not_used_in_rules = \Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->source_field_list         = \Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field_list         = \Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->source_field_date         = \Mockery::mock(Tracker_FormElement_Field_Date::class);
        $this->target_field_date         = \Mockery::mock(Tracker_FormElement_Field_Date::class);

        $this->a_field_not_used_in_rules->shouldReceive('getId')->andReturn(14);
        $this->source_field_list->shouldReceive('getId')->andReturn(12);
        $this->target_field_list->shouldReceive('getId')->andReturn(13);
        $this->source_field_date->shouldReceive('getId')->andReturn(15);
        $this->target_field_date->shouldReceive('getId')->andReturn(16);

        $this->tracker->shouldReceive('getId')->andReturn(110);

        $rules_list = new Tracker_Rule_List();
        $rules_list->setTrackerId($this->tracker->getId())
            ->setSourceFieldId($this->source_field_list->getId())
            ->setTargetFieldId($this->target_field_list->getId())
            ->setSourceValue('A')
            ->setTargetValue('B');

        $rules_date = new Tracker_Rule_Date();
        $rules_date->setTrackerId($this->tracker->getId())
            ->setSourceFieldId($this->source_field_date->getId())
            ->setTargetFieldId($this->target_field_date->getId())
            ->setComparator('<');

        $this->tracker_rules_manager->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([$rules_list]);
        $this->tracker_rules_manager->shouldReceive('getAllDateRulesByTrackerId')->andReturn([$rules_date]);
    }

    public function testItReturnsTrueIfTheFieldIsUsedInARuleList()
    {
        $this->assertTrue($this->tracker_rules_manager->isUsedInFieldDependency($this->source_field_list));
    }

    public function testItReturnsTrueIfTheFieldIsUsedInARuleDate()
    {
        $this->assertTrue($this->tracker_rules_manager->isUsedInFieldDependency($this->source_field_date));
    }

    public function testItReturnsFalseIfTheFieldIsNotUsedInARule()
    {
        $this->assertFalse($this->tracker_rules_manager->isUsedInFieldDependency($this->a_field_not_used_in_rules));
    }
}
