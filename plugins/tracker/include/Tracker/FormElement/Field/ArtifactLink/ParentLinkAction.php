<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\Artifact\Artifact;

class ParentLinkAction
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function linkParent(
        Artifact $artifact,
        PFUser $submitter,
        array $fields_data,
    ): bool {
        $artifact_link_field = $artifact->getAnArtifactLinkField($submitter);
        if ($artifact_link_field === null) {
            return false;
        }

        $artifact_link_field_id = $artifact_link_field->getId();

        if (
            ! isset($fields_data[$artifact_link_field_id]) ||
            ! isset($fields_data[$artifact_link_field_id][ArtifactLinkField::FIELDS_DATA_PARENT_KEY])
        ) {
            return false;
        }

        $nb_parent_linked = 0;
        foreach ($fields_data[$artifact_link_field_id][ArtifactLinkField::FIELDS_DATA_PARENT_KEY] as $parent_artifact_id) {
            $parent_artifact = $this->artifact_factory->getArtifactById((int) $parent_artifact_id);
            if ($parent_artifact === null) {
                continue;
            }

            if (
                $parent_artifact->linkArtifact(
                    $artifact->getId(),
                    $submitter,
                    ArtifactLinkField::TYPE_IS_CHILD
                )
            ) {
                $nb_parent_linked++;
            }
        }

        return $nb_parent_linked > 0;
    }
}
