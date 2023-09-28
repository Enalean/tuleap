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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class NewArtifactLinkChangesetValueBuilder
{
    public function __construct(private readonly RetrieveForwardLinks $forward_links_retriever)
    {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function buildFromPayload(
        Artifact $artifact,
        \Tracker_FormElement_Field_ArtifactLink $link_field,
        \PFUser $submitter,
        ArtifactValuesRepresentation $payload,
    ): NewArtifactLinkChangesetValue {
        $valid_payload = ValidArtifactLinkPayloadBuilder::buildPayloadAndCheckValidity($payload);

        if ($valid_payload->isAllLinksPayload()) {
            return NewArtifactLinkChangesetValue::fromParts(
                $link_field->getId(),
                $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
                Option::fromValue($this->buildForward($payload)),
                Option::nothing(NewParentLink::class),
                $this->buildReverse($payload)
            );
        }

        if ($valid_payload->hasNotDefinedLinksOrParent()) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                'Value should be \'links\' and an array of {"id": integer, ["type": string]} and/or \'parent\' with {"id": integer}'
            );
        }

        return NewArtifactLinkChangesetValue::fromParts(
            $link_field->getId(),
            $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
            $this->buildFromLinksKey($payload),
            $this->buildParent($payload),
            new CollectionOfReverseLinks([])
        );
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     * @return Option<NewParentLink>
     */
    private function buildParent(ArtifactValuesRepresentation $payload): Option
    {
        if ($payload->parent === null) {
            return Option::nothing(NewParentLink::class);
        }
        /** @psalm-var NewParentLink $new_parent_link */
        $new_parent_link = RESTNewParentLinkProxy::fromRESTPayload($payload->parent);
        return Option::fromValue($new_parent_link);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     * @return Option<CollectionOfForwardLinks>
     */
    private function buildFromLinksKey(ArtifactValuesRepresentation $payload): Option
    {
        if ($payload->links === null) {
            return Option::nothing(CollectionOfForwardLinks::class);
        }

        return Option::fromValue(
            new CollectionOfForwardLinks(
                array_map(
                    static fn(array $payload_link) => RESTForwardLinkProxy::fromPayload($payload_link),
                    $payload->links
                )
            )
        );
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildReverse(ArtifactValuesRepresentation $payload): CollectionOfReverseLinks
    {
        if ($payload->all_links === null) {
            return new CollectionOfReverseLinks([]);
        }

        return AllLinkPayloadParser::buildReverseLinks($payload->all_links);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildForward(ArtifactValuesRepresentation $payload): CollectionOfForwardLinks
    {
        if ($payload->all_links === null) {
            return new CollectionOfForwardLinks([]);
        }
        return AllLinkPayloadParser::buildForwardLinks($payload->all_links);
    }
}
