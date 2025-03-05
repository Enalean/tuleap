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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Container_Fieldset;
use Transition;
use Transition_PostAction_CIBuild;
use Transition_PostAction_Field_Date;
use Transition_PostAction_Field_Float;
use Transition_PostAction_Field_Int;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsMapper;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Workflow_Transition_Condition_CommentNotEmpty;
use Workflow_Transition_Condition_FieldNotEmpty;
use Workflow_Transition_Condition_Permissions;
use Workflow_Transition_ConditionFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionReplicatorTest extends TestCase
{
    private TransitionReplicator $transition_replicator;
    private Workflow_Transition_ConditionFactory&MockObject $condition_factory;
    private ConditionsUpdater&MockObject $conditions_updater;
    private PostActionsRetriever&MockObject $post_actions_retriever;
    private PostActionCollectionUpdater&MockObject $post_actions_updater;
    private PostActionsMapper $post_actions_mapper;

    protected function setUp(): void
    {
        $this->condition_factory      = $this->createMock(Workflow_Transition_ConditionFactory::class);
        $this->conditions_updater     = $this->createMock(ConditionsUpdater::class);
        $this->post_actions_retriever = $this->createMock(PostActionsRetriever::class);
        $this->post_actions_updater   = $this->createMock(PostActionCollectionUpdater::class);
        $this->post_actions_mapper    = new PostActionsMapper();

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->expects(self::once())->method('processEvent');

        $this->transition_replicator = new TransitionReplicator(
            $this->condition_factory,
            $this->conditions_updater,
            $this->post_actions_retriever,
            $this->post_actions_updater,
            $this->post_actions_mapper,
            $event_manager
        );
    }

    public function testReplicateCopiesConditionsAndPostActionsFromTransitionToTransition()
    {
        $from_transition     = $this->createMock(Transition::class);
        $to_transition       = $this->createMock(Transition::class);
        $not_empty_ids       = [195, 305];
        $not_empty_condition = $this->createMock(Workflow_Transition_Condition_FieldNotEmpty::class);
        $not_empty_condition
            ->method('getFieldIds')
            ->willReturn($not_empty_ids);
        $this->condition_factory
            ->method('getFieldNotEmptyCondition')
            ->willReturn($not_empty_condition);
        $is_comment_required = true;
        $comment_condition   = $this->createMock(Workflow_Transition_Condition_CommentNotEmpty::class);
        $comment_condition
            ->method('isCommentRequired')
            ->willReturn($is_comment_required);
        $this->condition_factory
            ->method('getCommentNotEmptyCondition')
            ->with($from_transition)
            ->willReturn($comment_condition);
        $permission_condition = $this->createMock(Workflow_Transition_Condition_Permissions::class);
        $permission_condition
            ->method('getAuthorizedUGroupsAsArray')
            ->willReturn([['ugroup_id' => '191'], ['ugroup_id' => '154_3']]);
        $this->condition_factory
            ->method('getPermissionsCondition')
            ->willReturn($permission_condition);

        $ci_build = $this->createMock(Transition_PostAction_CIBuild::class);
        $ci_build->method('getJobUrl')->willReturn('https://example.com');
        $this->post_actions_retriever
            ->method('getCIBuilds')
            ->willReturn([$ci_build]);

        $date_field = $this->createMock(Transition_PostAction_Field_Date::class);
        $date_field->method('getFieldId')->willReturn('197');
        $date_field->method('getValueType')->willReturn(Transition_PostAction_Field_Date::FILL_CURRENT_TIME);
        $this->post_actions_retriever
            ->method('getSetDateFieldValues')
            ->willReturn([$date_field]);

        $float_field = $this->createMock(Transition_PostAction_Field_Float::class);
        $float_field->method('getFieldId')->willReturn('201');
        $float_field->method('getValue')->willReturn(48.97);
        $this->post_actions_retriever
            ->method('getSetFloatFieldValues')
            ->willReturn([$float_field]);

        $int_field = $this->createMock(Transition_PostAction_Field_Int::class);
        $int_field->method('getFieldId')->willReturn('247');
        $int_field->method('getValue')->willReturn(-128);
        $this->post_actions_retriever
            ->method('getSetIntFieldValues')
            ->willReturn([$int_field]);

        $frozen_fields = $this->createMock(FrozenFields::class);
        $frozen_fields->method('getFieldIds')->willReturn([999]);
        $this->post_actions_retriever
            ->method('getFrozenFields')
            ->willReturn($frozen_fields);

        $fieldset_01 = $this->createMock(Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = $this->createMock(Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->method('getID')->willReturn('648');
        $fieldset_02->method('getID')->willReturn('701');

        $hidden_fieldsets = $this->createMock(HiddenFieldsets::class);
        $hidden_fieldsets->method('getFieldsets')->willReturn([
            $fieldset_01,
            $fieldset_02,
        ]);

        $this->post_actions_retriever
            ->method('getHiddenFieldsets')
            ->willReturn($hidden_fieldsets);

        $this->conditions_updater
            ->method('update')
            ->with($to_transition, ['191', '154_3'], $not_empty_ids, $is_comment_required);
        $this->post_actions_updater
            ->method('updateByTransition')
            ->with($to_transition, self::callback(static fn (PostActionCollection $collection) =>
                $collection->getCIBuildPostActions()[0]->getJobUrl() === 'https://example.com'
                    && $collection->getSetDateValuePostActions()[0]->getFieldId() === 197
                    && $collection->getSetDateValuePostActions()[0]->getValue() === Transition_PostAction_Field_Date::FILL_CURRENT_TIME
                    && $collection->getSetFloatValuePostActions()[0]->getFieldId() === 201
                    && $collection->getSetFloatValuePostActions()[0]->getValue() === 48.97
                    && $collection->getSetIntValuePostActions()[0]->getFieldId() === 247
                    && $collection->getSetIntValuePostActions()[0]->getValue() === -128
                    && $collection->getFrozenFieldsPostActions()[0]->getFieldIds() === [999]
                    && $collection->getHiddenFieldsetsPostActions()[0]->getFieldsetIds() === [648, 701]));

        $this->transition_replicator->replicate($from_transition, $to_transition);
    }
}
