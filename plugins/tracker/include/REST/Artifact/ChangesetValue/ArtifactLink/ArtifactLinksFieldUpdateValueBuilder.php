<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksFieldUpdateValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;

final class ArtifactLinksFieldUpdateValueBuilder
{
    public function __construct(
        private ArtifactLinksPayloadStructureChecker $payload_structure_checker,
        private ArtifactLinksPayloadExtractor $links_extractor,
        private ArtifactParentLinkPayloadExtractor $parent_link_extractor,
        private RetrieveForwardLinks $forward_links_retriever,
    ) {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function buildArtifactLinksFieldUpdateValue(
        \PFUser $submitter,
        Tracker_FormElement_Field_ArtifactLink $link_field,
        array $payload,
        ?Artifact $artifact,
    ): ArtifactLinksFieldUpdateValue {
        $this->payload_structure_checker->checkPayloadStructure($payload);

        $extracted_links = $this->links_extractor->extractValuesFromPayload($payload);

        return ArtifactLinksFieldUpdateValue::build(
            $link_field->getId(),
            $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
            $extracted_links,
            $this->parent_link_extractor->extractParentLinkFromPayload($payload)
        );
    }
}
