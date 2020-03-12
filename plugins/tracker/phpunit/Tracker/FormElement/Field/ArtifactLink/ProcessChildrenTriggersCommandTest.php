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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;

final class Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommandTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement
     */
    private $field;
    /**
     * @var Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand
     */
    private $command;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Workflow_Trigger_RulesManager
     */
    private $trigger_rules_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory
     */
    private $nature_factory;

    protected function setUp(): void
    {
        $this->trigger_rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $tracker                     = \Mockery::spy(\Tracker::class);
        $this->field                 = Mockery::mock(
            Tracker_FormElement_Field_ArtifactLink::class,
            [
                1,
                888,
                null,
                'artlink',
                'artifact link',
                '',
                true,
                'P',
                true,
                true,
                100
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact              = Mockery::mock(Tracker_Artifact::class);
        $this->user                  = new PFUser(['language_id' => 'en']);

        $this->nature_factory = \Mockery::spy(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory::class
        );
        $this->nature_factory->shouldReceive('getFromShortname')->andReturns(
            new NaturePresenter('', '', '', true, true)
        );

        $this->field->shouldReceive('getTracker')->andReturn($tracker);

        $this->command = new Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand(
            $this->field,
            $this->trigger_rules_manager
        );
    }

    public function testItCallsProcessChildrenTriggersWhenThereAreChanges(): void
    {
        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $changeset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
            ]
        );
        $previous_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value);
        $changeset_value->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $changeset_value_bis = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value_bis->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, ''),
                456 => new Tracker_ArtifactLinkInfo(456, 'art', 101, 1, 12345, '')
            ]
        );
        $new_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value_bis);
        $changeset_value_bis->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $this->trigger_rules_manager->shouldReceive('processChildrenTriggers')->with($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function testItCallsNothingWhenThereAreNotAnyChanges(): void
    {
        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_value    = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
            ]
        );
        $previous_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value);
        $changeset_value->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $new_changeset       = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_value_bis = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $changeset_value_bis->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
            ]
        );
        $new_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value_bis);
        $changeset_value_bis->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $this->trigger_rules_manager->shouldReceive('processChildrenTriggers')->with($this->artifact)->never();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function testItDoesntFailWhenPreviousChangesetHasNoValue(): void
    {
        $previous_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_value    = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value->shouldReceive('getValue')->andReturns(null);

        $new_changeset       = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_value_bis = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value_bis->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
            ]
        );
        $new_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value_bis);
        $changeset_value_bis->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $this->trigger_rules_manager->shouldReceive('processChildrenTriggers')->with($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function testItCallsProcessChildrenTriggersWhenNoPreviousChangeset(): void
    {
        $previous_changeset = null;

        $new_changeset       = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_value_bis = \Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $changeset_value_bis->shouldReceive('getValue')->andReturns(
            [
                123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
            ]
        );
        $new_changeset->shouldReceive('getValue')->with($this->field)->andReturns($changeset_value_bis);
        $changeset_value_bis->shouldReceive('getNaturePresenterFactory')->andReturns($this->nature_factory);

        $this->trigger_rules_manager->shouldReceive('processChildrenTriggers')->with($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }
}
