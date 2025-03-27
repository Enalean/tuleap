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

use Codendi_Request;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_ArtifactDao;
use Tracker_MasschangeDataValueExtractor;
use Tracker_Report;
use Tracker_Rule_List;
use Tracker_RuleFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveUsedListField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MasschangeUpdaterTest extends TestCase
{
    use GlobalResponseMock;

    private const USER_ID = 963;
    private Tracker_ArtifactDao&MockObject $artifact_dao;
    private RetrieveUsedListField $form_element_factory;
    private RetrieveArtifact $artifact_factory;
    private CreateNewChangesetStub $changeset_creator;

    protected function setUp(): void
    {
        $this->artifact_dao         = $this->createMock(Tracker_ArtifactDao::class);
        $this->form_element_factory = RetrieveUsedListFieldStub::withNoField();
        $this->artifact_factory     = RetrieveArtifactStub::withNoArtifact();
        $this->changeset_creator    = CreateNewChangesetStub::withNullReturnChangeset();
    }

    /**
     * @param list<int>               $masschange_artifact_ids
     * @param list<Tracker_Rule_List> $rules
     */
    private function update(
        Tracker $tracker,
        array $new_values,
        array $masschange_artifact_ids,
        bool $unsubscribe_from_notifications,
        array $rules,
    ): void {
        $request = new Codendi_Request([
            'masschange_aids' => $masschange_artifact_ids,
            'masschange-unsubscribe-option' => $unsubscribe_from_notifications,
            'artifact' => $new_values,
            'artifact_masschange_followup_comment' => '',
        ]);

        $rules_factory = $this->createStub(Tracker_RuleFactory::class);
        $rules_factory->method('getAllListRulesByTrackerWithOrder')->willReturn($rules);

        $masschange_data_value_extractor = $this->createStub(Tracker_MasschangeDataValueExtractor::class);
        $masschange_data_value_extractor->method('getNewValues')->willReturn($new_values);

        $event_manager = EventDispatcherStub::withIdentityCallback();

        $updater = new MasschangeUpdater(
            $tracker,
            $this->createStub(Tracker_Report::class),
            $masschange_data_value_extractor,
            $rules_factory,
            $this->form_element_factory,
            $this->artifact_factory,
            $this->artifact_dao,
            $event_manager,
            $this->changeset_creator
        );
        $updater->updateArtifacts(UserTestBuilder::buildWithId(self::USER_ID), $request);
        self::assertSame(1, $event_manager->getCallCount());
    }

    public function testUpdateArtifactsWithoutBeenUnsubscribedFromNotifications(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects($this->once())->method('augmentDataFromRequest');

        $new_values = [1 => 'Value01'];

        $artifact_201 = ArtifactTestBuilder::anArtifact(201)
                ->withChangesets(
                    ChangesetTestBuilder::aChangeset(352)
                                                                   ->build()
                )
                    ->build();
        $artifact_202 = ArtifactTestBuilder::anArtifact(202)
                ->withChangesets(
                    ChangesetTestBuilder::aChangeset(311)->build()
                )
                ->build();

        $this->artifact_factory = RetrieveArtifactStub::withArtifacts($artifact_201, $artifact_202);
        $expected_artifacts     = [201 => $artifact_201, 202 => $artifact_202];

        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            function (NewChangeset $changeset) use ($expected_artifacts) {
                $expected_artifact = $expected_artifacts[$changeset->getArtifact()->getId()];
                self::assertSame($changeset->getArtifact(), $expected_artifact);
                $expected_artifact_changeset = $changeset->getArtifact()->getLastChangeset();
                self::assertNotNull($expected_artifact_changeset);
                unset(
                    $expected_artifacts[$changeset->getArtifact()->getId()]
                ); //ensure we are not testing against the same artifact twice
                return $expected_artifact_changeset;
            }
        );
        $this->update($tracker, $new_values, [201, 202], false, []);
    }

    public function testUpdateArtifactsAndUserHasBeenUnsubscribedFromNotifications(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects($this->once())->method('augmentDataFromRequest');

        $new_values = [1 => 'Value01'];

        $user_can_view = UserTestBuilder::buildWithId(self::USER_ID);

        $artifact_201 = ArtifactTestBuilder::anArtifact(201)
                ->withChangesets(
                    ChangesetTestBuilder::aChangeset(352)
                                                                   ->build()
                )
                    ->userCanView($user_can_view)
                        ->build();
        $artifact_202 = ArtifactTestBuilder::anArtifact(202)
                ->withChangesets(
                    ChangesetTestBuilder::aChangeset(311)
                                                                   ->build()
                )
                    ->userCanView($user_can_view)
                        ->build();

        $this->artifact_factory = RetrieveArtifactStub::withArtifacts($artifact_201, $artifact_202);
        $matcher                = self::exactly(2);

        $this->artifact_dao->expects($matcher)->method('createUnsubscribeNotification')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(201, $parameters[0]);
                self::assertSame(self::USER_ID, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(202, $parameters[0]);
                self::assertSame(self::USER_ID, $parameters[1]);
            }
        });

        $expected_artifacts = [$artifact_201, $artifact_202];

        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            function (NewChangeset $changeset) use ($expected_artifacts) {
                $expected_artifact = $expected_artifacts[$changeset->getArtifact()->getId()];
                self::assertSame($changeset->getArtifact(), $expected_artifact);
                $expected_artifact_changeset = $changeset->getArtifact()->getLastChangeset();
                self::assertNotNull($expected_artifact_changeset);
                unset(
                    $expected_artifacts[$changeset->getArtifact()->getId()]
                ); //ensure we are not testing against the same artifact twice
                return $expected_artifact_changeset;
            }
        );

        $this->update($tracker, $new_values, [201, 202], true, []);
    }

    public function testDataRetrievedFromTheRequestIsConsolidated(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects($this->once())->method('augmentDataFromRequest')->with(
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

        $list_field_3201   = ListFieldBuilder::aListField(3201)->build();
        $source_bind_rule1 = ListStaticBindBuilder::aStaticBind(
            $list_field_3201
        )->withStaticValues([321 => 'Open', 322 => 'Closed'])->build();

        $list_field_3202                   = ListFieldBuilder::aListField(3202)->build();
        $source_and_target_bind_both_rules = ListStaticBindBuilder::aStaticBind(
            $list_field_3202
        )->withStaticValues([261 => 'Open', 262 => 'Closed'])->build();

        $list_field_3203   = ListFieldBuilder::aListField(3203)->build();
        $target_bind_rule2 = ListStaticBindBuilder::aStaticBind(
            $list_field_3203
        )->withStaticValues([333 => 'Code Yellow', 334 => 'Code Green'])->build();

        $this->form_element_factory = RetrieveUsedListFieldStub::withFields(
            $list_field_3201,
            $list_field_3202,
            $list_field_3203
        );

        $last_changeset         = ChangesetTestBuilder::aChangeset(968)->build();
        $artifact               = ArtifactTestBuilder::anArtifact(201)->withChangesets($last_changeset)->build();
        $this->artifact_factory = RetrieveArtifactStub::withArtifacts($artifact);

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

        $this->changeset_creator = CreateNewChangesetStub::withCallback(
            function (NewChangeset $changeset) {
                self::assertSame([1 => 'Value01', 3202 => 100], $changeset->getFieldsData());
                $expected_artifact_changeset = $changeset->getArtifact()->getLastChangeset();
                self::assertNotNull($expected_artifact_changeset);
                return $expected_artifact_changeset;
            }
        );

        $this->update($tracker, $new_values, [201], false, [$rule_1, $rule_2]);
    }
}
