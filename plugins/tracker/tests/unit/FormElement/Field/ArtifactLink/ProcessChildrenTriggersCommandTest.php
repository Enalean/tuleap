<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand;
use Tracker_Workflow_Trigger_RulesManager;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ProcessChildrenTriggersCommandTest extends TestCase
{
    private ArtifactLinkField $field;
    private Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand $command;
    private Artifact $artifact;
    private PFUser $user;
    private Tracker_Workflow_Trigger_RulesManager&MockObject $trigger_rules_manager;
    private TypePresenterFactory&MockObject $nature_factory;

    protected function setUp(): void
    {
        $this->trigger_rules_manager = $this->createMock(Tracker_Workflow_Trigger_RulesManager::class);
        $tracker                     = TrackerTestBuilder::aTracker()->build();
        $this->field                 = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->inTracker($tracker)->build();
        $this->artifact              = ArtifactTestBuilder::anArtifact(653421)->build();
        $this->user                  = new PFUser(['language_id' => 'en']);

        $this->nature_factory = $this->createMock(TypePresenterFactory::class);
        $this->nature_factory->method('getFromShortname')->willReturn(new TypePresenter('', '', '', true));

        $this->command = new Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand(
            $this->field,
            $this->trigger_rules_manager
        );
    }

    public function testItCallsProcessChildrenTriggersWhenThereAreChanges(): void
    {
        $previous_changeset = ChangesetTestBuilder::aChangeset(678)->build();
        $previous_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(534, $previous_changeset, $this->field)
                ->withForwardLinks([123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')])
                ->build(),
        );

        $new_changeset = ChangesetTestBuilder::aChangeset(679)->build();
        $new_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(535, $new_changeset, $this->field)
                ->withForwardLinks([
                    123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, ''),
                    456 => new Tracker_ArtifactLinkInfo(456, 'art', 101, 1, 12345, ''),
                ])
                ->build(),
        );

        $this->trigger_rules_manager->expects($this->once())->method('processChildrenTriggers')->with($this->artifact);

        $this->command->execute($this->artifact, $this->user, $new_changeset, [], $previous_changeset);
    }

    public function testItCallsNothingWhenThereAreNotAnyChanges(): void
    {
        $previous_changeset = ChangesetTestBuilder::aChangeset(678)->build();
        $previous_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(534, $previous_changeset, $this->field)
                ->withForwardLinks([123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')])
                ->build(),
        );

        $new_changeset = ChangesetTestBuilder::aChangeset(679)->build();
        $new_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(535, $new_changeset, $this->field)
                ->withForwardLinks([123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')])
                ->build(),
        );

        $this->trigger_rules_manager->expects($this->never())->method('processChildrenTriggers')->with($this->artifact);

        $this->command->execute($this->artifact, $this->user, $new_changeset, [], $previous_changeset);
    }

    public function testItDoesntFailWhenPreviousChangesetHasNoValue(): void
    {
        $previous_changeset = ChangesetTestBuilder::aChangeset(678)->build();
        $previous_changeset->setFieldValue($this->field);

        $new_changeset = ChangesetTestBuilder::aChangeset(679)->build();
        $new_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(535, $new_changeset, $this->field)
                ->withForwardLinks([123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')])
                ->build(),
        );

        $this->trigger_rules_manager->expects($this->once())->method('processChildrenTriggers')->with($this->artifact);

        $this->command->execute($this->artifact, $this->user, $new_changeset, [], $previous_changeset);
    }

    public function testItCallsProcessChildrenTriggersWhenNoPreviousChangeset(): void
    {
        $previous_changeset = null;

        $new_changeset = ChangesetTestBuilder::aChangeset(679)->build();
        $new_changeset->setFieldValue(
            $this->field,
            ChangesetValueArtifactLinkTestBuilder::aValue(535, $new_changeset, $this->field)
                ->withForwardLinks([123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')])
                ->build(),
        );

        $this->trigger_rules_manager->expects($this->once())->method('processChildrenTriggers')->with($this->artifact);

        $this->command->execute($this->artifact, $this->user, $new_changeset, [], $previous_changeset);
    }
}
