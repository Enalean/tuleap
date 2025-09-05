<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

/**
 * Update the link direction in order to ensure that it is correct resp. the
 * association definition.
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand implements
    Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand
{
    /** @var ArtifactLinkField */
    private $field;

    /** @var Tracker_Workflow_Trigger_RulesManager */
    private $trigger_rules_manager;

    public function __construct(
        ArtifactLinkField $field,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
    ) {
        $this->field                 = $field;
        $this->trigger_rules_manager = $trigger_rules_manager;
    }

    /**
     * @see Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand::execute()
     */
    #[\Override]
    public function execute(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
        if ($this->hasChanges($new_changeset, $previous_changeset)) {
            $this->trigger_rules_manager->processChildrenTriggers($artifact);
        }
    }

    private function hasChanges(
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
        if (! $previous_changeset) {
            return true;
        }

        $new_value      = $new_changeset->getValue($this->field);
        $previous_value = $previous_changeset->getValue($this->field);

        $diff = $new_value->getArtifactLinkInfoDiff($this->field->getTracker(), $previous_value);
        return $diff->hasChanges();
    }
}
