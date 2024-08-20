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

namespace Tuleap\Tracker\Masschange;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tracker;
use Tracker_Rule_List;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

final class MasschangeUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const USER_ID = 963;
    private \Tracker_ArtifactDao & MockObject $artifact_dao;
    private \Tracker_FormElementFactory & Stub $form_element_factory;
    private \Tracker_ArtifactFactory & Stub $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_dao         = $this->createMock(\Tracker_ArtifactDao::class);
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
        $this->artifact_factory     = $this->createStub(\Tracker_ArtifactFactory::class);
    }

    /**
     * @param list<int>               $masschange_artifact_ids
     * @param list<Tracker_Rule_List> $rules
     */
    private function update(
        \Tracker $tracker,
        array $new_values,
        array $masschange_artifact_ids,
        bool $unsubscribe_from_notifications,
        array $rules,
    ): void {
        $request = new \Codendi_Request([
            'masschange_aids' => $masschange_artifact_ids,
            'masschange-unsubscribe-option' => $unsubscribe_from_notifications,
            'artifact' => $new_values,
            'artifact_masschange_followup_comment' => '',
        ]);

        $rules_factory = $this->createStub(\Tracker_RuleFactory::class);
        $rules_factory->method('getAllListRulesByTrackerWithOrder')->willReturn($rules);

        $masschange_data_value_extractor = $this->createStub(\Tracker_MasschangeDataValueExtractor::class);
        $masschange_data_value_extractor->method('getNewValues')->willReturn($new_values);

        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->expects(self::once())->method('processEvent');

        $updater = new MasschangeUpdater(
            $tracker,
            $this->createStub(\Tracker_Report::class),
            $masschange_data_value_extractor,
            $rules_factory,
            $this->form_element_factory,
            $this->artifact_factory,
            $this->artifact_dao,
            $event_manager
        );
        $updater->updateArtifacts(UserTestBuilder::buildWithId(self::USER_ID), $request);
    }

    public function testUpdateArtifactsWithoutBeenUnsubscribedFromNotifications(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects(self::once())->method('augmentDataFromRequest');

        $new_values = [1 => 'Value01'];

        $artifact_201 = $this->createMock(Artifact::class);
        $artifact_201->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset('352')->build());
        $artifact_202 = $this->createMock(Artifact::class);
        $artifact_202->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset('311')->build());
        $this->artifact_factory->method('getArtifactById')->willReturnMap([
            [201, $artifact_201],
            [202, $artifact_202],
        ]);

        $artifact_201->expects(self::once())->method('createNewChangeset');
        $artifact_202->expects(self::once())->method('createNewChangeset');

        $this->update($tracker, $new_values, [201, 202], false, []);
    }

    public function testUpdateArtifactsAndUserHasBeenUnsubscribedFromNotifications(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects(self::once())->method('augmentDataFromRequest');

        $new_values = [1 => 'Value01'];

        $artifact_201 = $this->createMock(Artifact::class);
        $artifact_201->method('getId')->willReturn(201);
        $artifact_201->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset('352')->build());
        $artifact_201->method('userCanView')->willReturn(true);
        $artifact_202 = $this->createMock(Artifact::class);
        $artifact_202->method('getId')->willReturn(202);
        $artifact_202->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset('311')->build());
        $artifact_202->method('userCanView')->willReturn(true);
        $this->artifact_factory->method('getArtifactById')->willReturnMap([
            [201, $artifact_201],
            [202, $artifact_202],
        ]);

        $this->artifact_dao->expects(self::exactly(2))->method('createUnsubscribeNotification')->withConsecutive(
            [201, self::USER_ID],
            [202, self::USER_ID]
        );
        $artifact_201->expects(self::once())->method('createNewChangeset');
        $artifact_202->expects(self::once())->method('createNewChangeset');

        $this->update($tracker, $new_values, [201, 202], true, []);
    }

    public function testDataRetrievedFromTheRequestIsConsolidated(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects(self::once())->method('augmentDataFromRequest')->with(
            self::callback(
                static fn(
                    array $data,
                ) => isset($data['request_method_called']) && $data['request_method_called'] === 'artifact-masschange'
            )
        );

        $new_values = [1 => 'Value01'];

        $rule_1 = $this->createStub(Tracker_Rule_List::class);
        $rule_1->method('getSourceFieldId')->willReturn(3201);
        $rule_1->method('getTargetFieldId')->willReturn(3202);
        $rule_2 = $this->createStub(Tracker_Rule_List::class);
        $rule_2->method('getSourceFieldId')->willReturn(3202);
        $rule_2->method('getTargetFieldId')->willReturn(3203);

        $source_bind_rule1                 = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(3201)->build()
        )->withStaticValues([321 => 'Open', 322 => 'Closed'])->build();
        $source_and_target_bind_both_rules = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(3202)->build()
        )->withStaticValues([261 => 'Open', 262 => 'Closed'])->build();
        $target_bind_rule2                 = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(3303)->build()
        )->withStaticValues([333 => 'Code Yellow', 334 => 'Code Green'])->build();
        $this->form_element_factory->method('getUsedListFieldById')->willReturnMap([
            [$tracker, 3201, $source_bind_rule1->getField()],
            [$tracker, 3202, $source_and_target_bind_both_rules->getField()],
            [$tracker, 3203, $target_bind_rule2->getField()],
        ]);

        $artifact       = $this->createMock(Artifact::class);
        $last_changeset = ChangesetTestBuilder::aChangeset('968')->build();
        $artifact->method('getLastChangeset')->willReturn($last_changeset);
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        ChangesetValueListTestBuilder::aListOfValue(
            418,
            $last_changeset,
            $source_bind_rule1->getField()
        )->withValues([$source_bind_rule1->getValue(321)])->build();
        ChangesetValueListTestBuilder::aListOfValue(
            618,
            $last_changeset,
            $source_and_target_bind_both_rules->getField()
        )->withValues([])->build();
        ChangesetValueListTestBuilder::aListOfValue(
            374,
            $last_changeset,
            $target_bind_rule2->getField()
        )->withValues([$target_bind_rule2->getValue(333), $target_bind_rule2->getValue(334)])->build();

        $artifact->expects(self::once())->method('createNewChangeset')->with(
            [1 => 'Value01', 3202 => 100],
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything(),
        );

        $this->update($tracker, $new_values, [201], false, [$rule_1, $rule_2]);
    }
}
