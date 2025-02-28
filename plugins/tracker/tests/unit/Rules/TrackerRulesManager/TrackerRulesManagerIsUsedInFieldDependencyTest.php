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
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_Rule_Date;
use Tracker_Rule_List;
use Tracker_RulesManager;
use TrackerFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;

final class TrackerRulesManagerIsUsedInFieldDependencyTest extends TestCase
{
    private Tracker_RulesManager&MockObject $tracker_rules_manager;

    private Tracker_FormElement_Field_Selectbox $source_field_list;

    private Tracker_FormElement_Field_Selectbox $a_field_not_used_in_rules;

    private Tracker_FormElement_Field_Date $source_field_date;

    public function setUp(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(110)->build();

        $formelement_factory          = $this->createMock(Tracker_FormElementFactory::class);
        $frozen_fields_dao            = $this->createMock(FrozenFieldsDao::class);
        $tracker_rules_list_validator = $this->createMock(TrackerRulesListValidator::class);
        $tracker_rules_date_validator = $this->createMock(TrackerRulesDateValidator::class);
        $tracker_factory              = $this->createMock(TrackerFactory::class);

        $this->tracker_rules_manager = $this->getMockBuilder(Tracker_RulesManager::class)
            ->onlyMethods(['getAllListRulesByTrackerWithOrder', 'getAllDateRulesByTrackerId'])
            ->setConstructorArgs([$tracker,
                $formelement_factory,
                $frozen_fields_dao,
                $tracker_rules_list_validator,
                $tracker_rules_date_validator,
                $tracker_factory,
                new NullLogger(),
            ])->getMock();

        $this->a_field_not_used_in_rules = ListFieldBuilder::aListField(14)->build();
        $this->source_field_list         = ListFieldBuilder::aListField(12)->build();
        $target_field_list               = ListFieldBuilder::aListField(13)->build();
        $this->source_field_date         = DateFieldBuilder::aDateField(15)->build();
        $target_field_date               = DateFieldBuilder::aDateField(16)->build();

        $rules_list = new Tracker_Rule_List();
        $rules_list->setTrackerId($tracker->getId())
            ->setSourceFieldId($this->source_field_list->getId())
            ->setTargetFieldId($target_field_list->getId())
            ->setSourceValue('A')
            ->setTargetValue('B');

        $rules_date = new Tracker_Rule_Date();
        $rules_date->setTrackerId($tracker->getId())
            ->setSourceFieldId($this->source_field_date->getId())
            ->setTargetFieldId($target_field_date->getId())
            ->setComparator('<');

        $this->tracker_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn([$rules_list]);
        $this->tracker_rules_manager->method('getAllDateRulesByTrackerId')->willReturn([$rules_date]);
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
