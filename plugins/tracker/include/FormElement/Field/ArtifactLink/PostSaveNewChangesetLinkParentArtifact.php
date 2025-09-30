<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand;
use Tuleap\Tracker\Artifact\Artifact;

class PostSaveNewChangesetLinkParentArtifact implements
    Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetCommand
{
    /**
     * @var ParentLinkAction
     */
    private $parent_link_action;

    public function __construct(ParentLinkAction $parent_link_action)
    {
        $this->parent_link_action = $parent_link_action;
    }

    #[\Override]
    public function execute(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ): void {
        $this->parent_link_action->linkParent(
            $artifact,
            $submitter,
            $fields_data
        );
    }
}
