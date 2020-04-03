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

namespace Tuleap\Tracker;

use Codendi_Request;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tracker_MasschangeDataValueExtractor;
use Tracker_MasschangeUpdater;
use Tracker_Report;
use Tracker_Rule_List;
use Tracker_RuleFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;

final class MasschangeUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['Response'] = Mockery::spy(BaseLayout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);
        parent::tearDown();
    }

    public function testUpdateArtifactsWithoutBeenUnsubscribedFromNotifications(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(123);
        $tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $tracker->shouldReceive('augmentDataFromRequest');

        $request = Mockery::spy(Codendi_Request::class);
        $request->shouldReceive('get')->with('masschange_aids')->andReturn([201, 202]);
        $request->shouldReceive('get')->with('masschange-unsubscribe-option')->andReturn(false);
        $new_values = [1 => 'Value01'];
        $request->shouldReceive('get')->with('artifact')->andReturn($new_values);
        $request->shouldReceive('get')->with('artifact_masschange_followup_comment')->andReturn('');

        $masschange_data_value_extractor = Mockery::mock(Tracker_MasschangeDataValueExtractor::class);
        $masschange_data_value_extractor->shouldReceive('getNewValues')->andReturn($new_values);

        $rules_factory = Mockery::mock(Tracker_RuleFactory::class);
        $rules_factory->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([]);

        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_201 = Mockery::mock(Tracker_Artifact::class);
        $artifact_201->shouldReceive('getLastChangeset')->andReturn(Mockery::mock(Tracker_Artifact_Changeset::class));
        $artifact_201->shouldReceive('createNewChangeset')->once();
        $artifact_factory->shouldReceive('getArtifactById')->with(201)->andReturn($artifact_201);
        $artifact_202 = Mockery::mock(Tracker_Artifact::class);
        $artifact_202->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $artifact_202->shouldReceive('createNewChangeset')->once();
        $artifact_factory->shouldReceive('getArtifactById')->with(202)->andReturn($artifact_202);

        $event_manager = Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->once();

        $masschange_updater = new Tracker_MasschangeUpdater(
            $tracker,
            Mockery::mock(Tracker_Report::class),
            $masschange_data_value_extractor,
            $rules_factory,
            Mockery::mock(Tracker_FormElementFactory::class),
            $artifact_factory,
            Mockery::mock(Tracker_ArtifactDao::class),
            $event_manager
        );
        $masschange_updater->updateArtifacts(Mockery::mock(PFUser::class), $request);
    }

    public function testUpdateArtifactsAndUserHasBeenUnsubscribedFromNotifications(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(123);
        $tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $tracker->shouldReceive('augmentDataFromRequest');

        $request = Mockery::spy(Codendi_Request::class);
        $request->shouldReceive('get')->with('masschange_aids')->andReturn([201, 202]);
        $request->shouldReceive('get')->with('masschange-unsubscribe-option')->andReturn(true);
        $new_values = [1 => 'Value01'];
        $request->shouldReceive('get')->with('artifact')->andReturn($new_values);
        $request->shouldReceive('get')->with('artifact_masschange_followup_comment')->andReturn('');

        $masschange_data_value_extractor = Mockery::mock(Tracker_MasschangeDataValueExtractor::class);
        $masschange_data_value_extractor->shouldReceive('getNewValues')->andReturn($new_values);

        $rules_factory = Mockery::mock(Tracker_RuleFactory::class);
        $rules_factory->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([]);

        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_201 = Mockery::mock(Tracker_Artifact::class);
        $artifact_201->shouldReceive('getId')->andReturn(201);
        $artifact_201->shouldReceive('getLastChangeset')->andReturn(Mockery::mock(Tracker_Artifact_Changeset::class));
        $artifact_201->shouldReceive('userCanView')->andReturn(true);
        $artifact_201->shouldReceive('createNewChangeset');
        $artifact_factory->shouldReceive('getArtifactById')->with(201)->andReturn($artifact_201);
        $artifact_202 = Mockery::mock(Tracker_Artifact::class);
        $artifact_202->shouldReceive('getLastChangeset')->andReturn(Mockery::spy(Tracker_Artifact_Changeset::class));
        $artifact_202->shouldReceive('getId')->andReturn(202);
        $artifact_202->shouldReceive('userCanView')->andReturn(true);
        $artifact_202->shouldReceive('createNewChangeset');
        $artifact_factory->shouldReceive('getArtifactById')->with(202)->andReturn($artifact_202);

        $artifact_dao = Mockery::mock(Tracker_ArtifactDao::class);

        $event_manager = Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->once();

        $masschange_updater = new Tracker_MasschangeUpdater(
            $tracker,
            Mockery::mock(Tracker_Report::class),
            $masschange_data_value_extractor,
            $rules_factory,
            Mockery::mock(Tracker_FormElementFactory::class),
            $artifact_factory,
            $artifact_dao,
            $event_manager
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(963);

        $artifact_dao->shouldReceive('createUnsubscribeNotification')->with(201, 963)->atLeast()->once();
        $artifact_dao->shouldReceive('createUnsubscribeNotification')->with(202, 963)->atLeast()->once();

        $masschange_updater->updateArtifacts($user, $request);
    }

    public function testDataRetrievedFromTheRequestIsConsolidated(): void
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(123);
        $tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $tracker->shouldReceive('augmentDataFromRequest')->with(
            Mockery::on(static function (array $data): bool {
                return isset($data['request_method_called']) && $data['request_method_called'] === 'artifact-masschange';
            })
        );

        $request = Mockery::spy(Codendi_Request::class);
        $request->shouldReceive('get')->with('masschange_aids')->andReturn([201]);
        $request->shouldReceive('get')->with('masschange-unsubscribe-option')->andReturn(false);
        $new_values = [1 => 'Value01'];
        $request->shouldReceive('get')->with('artifact')->andReturn($new_values);
        $request->shouldReceive('get')->with('artifact_masschange_followup_comment')->andReturn('');

        $masschange_data_value_extractor = Mockery::mock(Tracker_MasschangeDataValueExtractor::class);
        $masschange_data_value_extractor->shouldReceive('getNewValues')->andReturn($new_values);

        $rules_factory = Mockery::mock(Tracker_RuleFactory::class);
        $rule_1 = Mockery::mock(Tracker_Rule_List::class);
        $rule_1->shouldReceive('getSourceFieldId')->andReturn(3201);
        $rule_1->shouldReceive('getTargetFieldId')->andReturn(3202);
        $rule_2 = Mockery::mock(Tracker_Rule_List::class);
        $rule_2->shouldReceive('getSourceFieldId')->andReturn(3202);
        $rule_2->shouldReceive('getTargetFieldId')->andReturn(3203);
        $rules_factory->shouldReceive('getAllListRulesByTrackerWithOrder')->andReturn([$rule_1, $rule_2]);

        $form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $source_field_rule1 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $source_field_rule1->shouldReceive('getId')->andReturn(3201);
        $source_and_target_field_both_rules = Mockery::mock(Tracker_FormElement_Field_List::class);
        $source_and_target_field_both_rules->shouldReceive('getId')->andReturn(3202);
        $target_field_rule2 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $target_field_rule2->shouldReceive('getId')->andReturn(3203);
        $form_element_factory->shouldReceive('getUsedListFieldById')->with($tracker, 3201)->andReturn($source_field_rule1);
        $form_element_factory->shouldReceive('getUsedListFieldById')->with($tracker, 3202)->andReturn($source_and_target_field_both_rules);
        $form_element_factory->shouldReceive('getUsedListFieldById')->with($tracker, 3203)->andReturn($target_field_rule2);

        $artifact       = Mockery::mock(Tracker_Artifact::class);
        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_factory->shouldReceive('getArtifactById')->andReturn($artifact);

        $changeset_value_source_rule1 = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_source_rule1->shouldReceive('getValue')->andReturn([321]);
        $source_field_rule1->shouldReceive('isNone')->andReturn(false);
        $last_changeset->shouldReceive('getValue')->with($source_field_rule1)->andReturn($changeset_value_source_rule1);
        $changeset_value_source_and_target = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $changeset_value_source_and_target->shouldReceive('getValue')->andReturn('');
        $source_and_target_field_both_rules->shouldReceive('isNone')->andReturn(true);
        $last_changeset->shouldReceive('getValue')->with($source_and_target_field_both_rules)->andReturn($changeset_value_source_and_target);
        $target_value_rule2 = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $target_value_rule2->shouldReceive('getValue')->andReturn([333, 334]);
        $target_field_rule2->shouldReceive('isNone')->andReturn(false);
        $last_changeset->shouldReceive('getValue')->with($target_field_rule2)->andReturn($target_value_rule2);

        $artifact->shouldReceive('createNewChangeset')->with(
            [1 => 'Value01', 3202 => 100],
            Mockery::any(),
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        );

        $event_manager = Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->once();

        $masschange_updater = new Tracker_MasschangeUpdater(
            $tracker,
            Mockery::mock(Tracker_Report::class),
            $masschange_data_value_extractor,
            $rules_factory,
            $form_element_factory,
            $artifact_factory,
            Mockery::mock(Tracker_ArtifactDao::class),
            $event_manager
        );
        $masschange_updater->updateArtifacts(Mockery::mock(PFUser::class), $request);
    }
}
