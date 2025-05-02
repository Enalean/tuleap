<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use EventManager;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_ReferenceManager;
use Tracker_Workflow_Trigger_RulesManager;
use Tracker_Workflow_Trigger_TriggerRuleCollection;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkValueSaverTest extends TestCase
{
    use GlobalResponseMock;

    private const CHANGESET_VALUE_ID = 56;

    private ArtifactLinkField $field;
    private ArtifactLinkValueSaver $saver;
    private Tracker_ReferenceManager&MockObject $reference_manager;
    private Artifact $initial_linked_artifact;
    private Artifact $some_artifact;
    private Artifact $other_artifact;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private ArtifactLinkChangesetValue $previous_changesetvalue;
    private PFUser $user;
    private ArtifactLinkFieldValueDao&MockObject $dao;
    private ArtifactLinksUsageDao&MockObject $artifact_link_usage_dao;
    private Tracker $tracker;
    private Tracker $tracker_child;
    private Artifact $another_artifact;
    private Tracker_Workflow_Trigger_RulesManager&MockObject $rules_manager;

    protected function setUp(): void
    {
        $this->reference_manager = $this->createMock(Tracker_ReferenceManager::class);
        $this->artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $this->dao               = $this->createMock(ArtifactLinkFieldValueDao::class);

        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->withId(101)->build();

        $this->tracker       = TrackerTestBuilder::aTracker()->withProject($project)->withId(102)->build();
        $this->tracker_child = TrackerTestBuilder::aTracker()->withProject($project)->withId(101)->build();
        $this->field         = ArtifactLinkFieldBuilder::anArtifactLinkField(64153)->inTracker($this->tracker)->build();

        $this->tracker->setChildren([$this->tracker_child]);
        $this->tracker_child->setChildren([]);

        $this->initial_linked_artifact = ArtifactTestBuilder::anArtifact(36)
            ->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(361)->build())
            ->build();

        $this->some_artifact = ArtifactTestBuilder::anArtifact(456)
            ->inTracker($this->tracker_child)
            ->withChangesets(ChangesetTestBuilder::aChangeset(4561)->build())
            ->build();

        $this->other_artifact = ArtifactTestBuilder::anArtifact(457)
            ->inTracker($this->tracker_child)
            ->withChangesets(ChangesetTestBuilder::aChangeset(4571)->build())
            ->build();

        $this->another_artifact = ArtifactTestBuilder::anArtifact(458)
            ->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(4581)->build())
            ->build();

        $this->artifact_factory->method('getArtifactById')->willReturnCallback(fn(int $id) => match ($id) {
            36  => $this->initial_linked_artifact,
            456 => $this->some_artifact,
            457 => $this->other_artifact,
            458 => $this->another_artifact,
        });

        $this->previous_changesetvalue = ChangesetValueArtifactLinkTestBuilder::aValue(12, ChangesetTestBuilder::aChangeset(3521)->build(), $this->field)
            ->withLinks([36 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->initial_linked_artifact, '')])
            ->build();

        $this->user = new PFUser([
            'language_id' => 'en',
            'user_id'     => 101,
        ]);

        $this->artifact_link_usage_dao = $this->createMock(ArtifactLinksUsageDao::class);
        $this->rules_manager           = $this->createMock(Tracker_Workflow_Trigger_RulesManager::class);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $this->saver = new ArtifactLinkValueSaver(
            $this->artifact_factory,
            $this->dao,
            $this->reference_manager,
            $event_manager,
            $this->artifact_link_usage_dao,
            $this->rules_manager
        );

        Tracker_ArtifactFactory::setInstance($this->artifact_factory);
    }

    protected function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testItRemovesACrossReference(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(645)->inTracker($this->tracker)->build();

        $value = [
            'list_of_artifactlinkinfo' => [],
            'removed_values'           => [
                36 => 1,
            ],
        ];

        $this->artifact_factory->method('getArtifactsByArtifactIdList')
            ->willReturnCallback(fn(array $ids) => match ($ids) {
                []   => [],
                [36] => $this->initial_linked_artifact,
            });

        $this->reference_manager->expects($this->once())->method('removeBetweenTwoArtifacts')
            ->with($artifact, $this->initial_linked_artifact, $this->user);
        $this->dao->expects($this->never())->method('create');

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItAddsACrossReference(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(645)->inTracker($this->tracker)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->initial_linked_artifact, 'fixed_in'),
            ],
            'removed_values'           => [],
        ];

        $this->dao->expects($this->once())->method('create')->willReturn(true);

        $this->reference_manager->expects($this->once())->method('insertBetweenTwoArtifacts')
            ->with($artifact, $this->initial_linked_artifact, $this->user);
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItCallsOnlyOneTimeCreateInDBIfAllArtifactsAreInTheSameTracker(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(65)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in'),
            ],
            'removed_values'           => [],
        ];

        $this->dao->expects($this->once())->method('create');
        $this->reference_manager->method('insertBetweenTwoArtifacts');
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItUsesArtifactLinkNature(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(65)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in'),
            ],
            'removed_values'           => [],
        ];

        $this->dao->expects($this->once())->method('create')
            ->with(self::anything(), '_is_child', self::anything(), self::anything(), self::anything());
        $this->reference_manager->method('insertBetweenTwoArtifacts');
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItUsesDefaultArtifactLinkNature(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(54)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, ''),
            ],
            'removed_values'           => [],
        ];

        $this->field->setTracker($this->tracker_child);

        $this->dao->expects($this->once())->method('create')
            ->with(self::anything(), null, self::anything(), self::anything(), self::anything());
        $this->reference_manager->method('insertBetweenTwoArtifacts');
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItUsesIsChildArtifactLinkTypeIfAHierarchyIsDefined(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, ''),
            ],
            'removed_values'           => [],
        ];

        $this->artifact_link_usage_dao->method('isTypeDisabledInProject')->with(101, '_is_child')->willReturn(false);

        $this->dao->expects($this->once())->method('create')
            ->with(self::anything(), '_is_child', self::anything(), self::anything(), self::anything());
        $this->reference_manager->method('insertBetweenTwoArtifacts');
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItDoesNotUseIsChildArtifactLinkTypeIfTargetTrackerIsNotChildInHierarchy(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(612)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->another_artifact, '_is_child'),
            ],
            'removed_values'           => [],
        ];

        $this->dao->expects($this->once())->method('create')
            ->with(self::anything(), null, self::anything(), self::anything(), self::anything());
        $this->rules_manager->method('getForTargetTracker')->willReturn(new Tracker_Workflow_Trigger_TriggerRuleCollection());
        $this->reference_manager->method('insertBetweenTwoArtifacts');
        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(false);

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }

    public function testItReturnsNullIfProjectUsesArtifactLinkTypes(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();

        $value = [
            'list_of_artifactlinkinfo' => [
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, ''),
            ],
            'removed_values'           => [],
        ];

        $this->artifact_link_usage_dao->method('isProjectUsingArtifactLinkTypes')->willReturn(true);

        $this->dao->expects($this->once())->method('create')
            ->with(self::anything(), null, self::anything(), self::anything(), self::anything());
        $this->reference_manager->method('insertBetweenTwoArtifacts');

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            self::CHANGESET_VALUE_ID,
            $value
        );
    }
}
