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

namespace Tuleap\Tracker\Workflow\SimpleMode;

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsMapper;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

class TransitionReplicatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionReplicator */
    private $transition_replicator;
    /** @var Mockery\MockInterface */
    private $condition_factory;
    /** @var Mockery\MockInterface */
    private $conditions_updater;
    /** @var Mockery\MockInterface */
    private $post_actions_retriever;
    /** @var Mockery\MockInterface */
    private $post_actions_updater;
    /** @var PostActionsMapper */
    private $post_actions_mapper;

    protected function setUp(): void
    {
        $this->condition_factory      = Mockery::mock(\Workflow_Transition_ConditionFactory::class);
        $this->conditions_updater     = Mockery::mock(ConditionsUpdater::class);
        $this->post_actions_retriever = Mockery::mock(PostActionsRetriever::class);
        $this->post_actions_updater   = Mockery::mock(PostActionCollectionUpdater::class);
        $this->post_actions_mapper    = new PostActionsMapper();
        $this->transition_replicator  = new TransitionReplicator(
            $this->condition_factory,
            $this->conditions_updater,
            $this->post_actions_retriever,
            $this->post_actions_updater,
            $this->post_actions_mapper,
            Mockery::mock(EventManager::class)->shouldReceive('processEvent')->once()->getMock()
        );
    }

    public function testReplicateCopiesConditionsAndPostActionsFromTransitionToTransition()
    {
        $from_transition     = Mockery::mock(\Transition::class);
        $to_transition       = Mockery::mock(\Transition::class);
        $not_empty_ids       = [195, 305];
        $not_empty_condition = Mockery::mock(\Workflow_Transition_Condition_FieldNotEmpty::class)
            ->shouldReceive('getFieldIds')
            ->andReturn($not_empty_ids)
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getFieldNotEmptyCondition')
            ->andReturn($not_empty_condition);
        $is_comment_required = true;
        $comment_condition = Mockery::mock(\Workflow_Transition_Condition_CommentNotEmpty::class)
            ->shouldReceive('isCommentRequired')
            ->andReturn($is_comment_required)
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getCommentNotEmptyCondition')
            ->with($from_transition)
            ->andReturn($comment_condition);
        $permission_condition = Mockery::mock(\Workflow_Transition_Condition_Permissions::class)
            ->shouldReceive('getAuthorizedUGroupsAsArray')
            ->andReturn([['ugroup_id' => '191'], ['ugroup_id' => '154_3']])
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getPermissionsCondition')
            ->andReturn($permission_condition);

        $ci_build = Mockery::mock(\Transition_PostAction_CIBuild::class);
        $ci_build->shouldReceive('getJobUrl')->andReturn('https://example.com');
        $this->post_actions_retriever
            ->shouldReceive('getCIBuilds')
            ->andReturn([$ci_build]);

        $date_field = Mockery::mock(\Transition_PostAction_Field_Date::class);
        $date_field->shouldReceive('getFieldId')->andReturn('197');
        $date_field->shouldReceive('getValueType')->andReturn(\Transition_PostAction_Field_Date::FILL_CURRENT_TIME);
        $this->post_actions_retriever
            ->shouldReceive('getSetDateFieldValues')
            ->andReturn([$date_field]);

        $float_field = Mockery::mock(\Transition_PostAction_Field_Float::class);
        $float_field->shouldReceive('getFieldId')->andReturn('201');
        $float_field->shouldReceive('getValue')->andReturn(48.97);
        $this->post_actions_retriever
            ->shouldReceive('getSetFloatFieldValues')
            ->andReturn([$float_field]);

        $int_field = Mockery::mock(\Transition_PostAction_Field_Int::class);
        $int_field->shouldReceive('getFieldId')->andReturn('247');
        $int_field->shouldReceive('getValue')->andReturn(-128);
        $this->post_actions_retriever
            ->shouldReceive('getSetIntFieldValues')
            ->andReturn([$int_field]);

        $frozen_fields = Mockery::mock(FrozenFields::class);
        $frozen_fields->shouldReceive('getFieldIds')->andReturn([999]);
        $this->post_actions_retriever
            ->shouldReceive('getFrozenFields')
            ->andReturn($frozen_fields);

        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->shouldReceive('getID')->andReturn('648');
        $fieldset_02->shouldReceive('getID')->andReturn('701');

        $hidden_fieldsets = Mockery::mock(HiddenFieldsets::class);
        $hidden_fieldsets->shouldReceive('getFieldsets')->andReturn([
            $fieldset_01,
            $fieldset_02
        ]);

        $this->post_actions_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->andReturn($hidden_fieldsets);

        $this->conditions_updater
            ->shouldReceive('update')
            ->with($to_transition, ['191', '154_3'], $not_empty_ids, $is_comment_required);
        $this->post_actions_updater
            ->shouldReceive('updateByTransition', $to_transition, new PostActionCollection(
                new CIBuildValue('https://example.com'),
                new SetDateValue(197, \Transition_PostAction_Field_Date::FILL_CURRENT_TIME),
                new SetFloatValue(201, 48.97),
                new SetIntValue(247, -128),
                new FrozenFieldsValue([999]),
                new HiddenFieldsetsValue([648, 701])
            ));

        $this->transition_replicator->replicate($from_transition, $to_transition);
    }
}
