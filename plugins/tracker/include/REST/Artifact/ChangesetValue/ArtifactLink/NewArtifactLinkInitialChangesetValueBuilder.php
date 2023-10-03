<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class NewArtifactLinkInitialChangesetValueBuilder
{
    private const LINKS_KEY = 'links';

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function buildFromPayload(
        \Tracker_FormElement_Field_ArtifactLink $link_field,
        ArtifactValuesRepresentation $payload,
    ): NewArtifactLinkInitialChangesetValue {
        $valid_payload = ValidArtifactLinkPayloadBuilder::buildPayloadAndCheckValidity($payload);

        if ($valid_payload->isAllLinksPayload()) {
            return NewArtifactLinkInitialChangesetValue::fromParts(
                $link_field->getId(),
                $this->buildForward($payload),
                $this->buildParent($payload),
                $this->buildReverse($payload)
            );
        }

        return NewArtifactLinkInitialChangesetValue::fromParts(
            $link_field->getId(),
            $this->buildLinks($payload),
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
     */
    private function buildLinks(ArtifactValuesRepresentation $payload): CollectionOfForwardLinks
    {
        if ($payload->links === null) {
            return new CollectionOfForwardLinks([]);
        }
        $links = array_map(
            static fn(array $link_payload) => RESTForwardLinkProxy::fromPayload($link_payload),
            $payload->toArray()[self::LINKS_KEY]
        );
        return new CollectionOfForwardLinks($links);
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
}
