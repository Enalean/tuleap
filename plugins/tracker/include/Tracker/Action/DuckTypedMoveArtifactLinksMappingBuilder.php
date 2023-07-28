<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PFUser;
use Tracker;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveAnArtifactLinkField;

final class DuckTypedMoveArtifactLinksMappingBuilder implements BuildArtifactLinksMappingForDuckTypedMove
{
    public function __construct(
        private readonly RetrieveAnArtifactLinkField $retrieve_an_artifact_link_field,
        private readonly RetrieveForwardLinks $retrieve_forward_links,
    ) {
    }

    public function buildMapping(
        Tracker $source_tracker,
        Artifact $artifact,
        PFUser $user,
    ): Tracker_XML_Importer_ArtifactImportedMapping {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();

        $source_artifact_link_field = $this->retrieve_an_artifact_link_field->getAnArtifactLinkField($user, $source_tracker);
        if (! $source_artifact_link_field) {
            return $mapping;
        }

        $forward_links = $this->retrieve_forward_links->retrieve(
            $user,
            $source_artifact_link_field,
            $artifact
        );

        foreach ($forward_links->getArtifactLinks() as $link) {
            $mapping->add($link->getTargetArtifactId(), $link->getTargetArtifactId());
        }

        return $mapping;
    }
}
