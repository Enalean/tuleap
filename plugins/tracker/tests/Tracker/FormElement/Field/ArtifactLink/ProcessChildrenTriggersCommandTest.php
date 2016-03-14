<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommandTest extends TuleapTestCase {

    protected $field;
    protected $command;
    protected $artifact;
    protected $user;
    protected $trigger_rules_manager;

    public function setUp() {
        parent::setUp();
        $this->trigger_rules_manager = mock('Tracker_Workflow_Trigger_RulesManager');
        $this->field                 = anArtifactLinkField()->build();
        $this->artifact              = anArtifact()->build();
        $this->user                  = aUser()->build();

        $this->command = new Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand(
            $this->field,
            $this->trigger_rules_manager
        );
    }

    public function itCallsProcessChildrenTriggersWhenThereAreChanges() {
        $previous_changeset = mock('Tracker_Artifact_Changeset');
        stub($previous_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        $new_changeset = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, ''),
                        456 => new Tracker_ArtifactLinkInfo(456, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        expect($this->trigger_rules_manager)->processChildrenTriggers($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function itCallsNothingWhenThereAreNotAnyChanges() {
        $previous_changeset = mock('Tracker_Artifact_Changeset');
        stub($previous_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        $new_changeset = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        expect($this->trigger_rules_manager)->processChildrenTriggers($this->artifact)->never();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function itDoesntFailWhenPreviousChangesetHasNoValue() {
        $previous_changeset = mock('Tracker_Artifact_Changeset');
        stub($previous_changeset)->getValue($this->field)->returns(null);

        $new_changeset = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        expect($this->trigger_rules_manager)->processChildrenTriggers($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }

    public function itCallsProcessChildrenTriggersWhenNoPreviousChangeset() {
        $previous_changeset = null;

        $new_changeset = mock('Tracker_Artifact_Changeset');
        stub($new_changeset)->getValue($this->field)->returns(
            aChangesetValueArtifactLink()
                ->withArtifactLinks(
                    array(
                        123 => new Tracker_ArtifactLinkInfo(123, 'art', 101, 1, 12345, '')
                    )
                )->build()
        );

        expect($this->trigger_rules_manager)->processChildrenTriggers($this->artifact)->once();

        $this->command->execute($this->artifact, $this->user, $new_changeset, $previous_changeset);
    }
}
