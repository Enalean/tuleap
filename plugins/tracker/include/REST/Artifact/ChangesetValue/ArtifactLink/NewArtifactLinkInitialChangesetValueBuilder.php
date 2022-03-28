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

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;

final class NewArtifactLinkInitialChangesetValueBuilder
{
    private const PARENT_KEY = 'parent';
    private const LINKS_KEY  = 'links';

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function buildFromPayload(
        \Tracker_FormElement_Field_ArtifactLink $link_field,
        array $payload,
    ): NewArtifactLinkInitialChangesetValue {
        $payload_has_parent_key = $this->doesPayloadHaveAParentKey($payload);
        $payload_has_links_key  = $this->doesPayloadHaveALinksKey($payload);

        if (! $payload_has_parent_key && ! $payload_has_links_key) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                'Value should be \'links\' and an array of {"id": integer, ["type": string]} and/or \'parent\' with {"id": integer}'
            );
        }

        return NewArtifactLinkInitialChangesetValue::fromParts(
            $link_field->getId(),
            $this->buildLinks($payload_has_links_key, $payload),
            $this->buildParent($payload_has_parent_key, $payload),
        );
    }

    private function doesPayloadHaveAParentKey(array $payload): bool
    {
        return array_key_exists(self::PARENT_KEY, $payload);
    }

    private function doesPayloadHaveALinksKey(array $payload): bool
    {
        return array_key_exists(self::LINKS_KEY, $payload)
            && is_array($payload[self::LINKS_KEY]);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildParent(bool $payload_has_parent_key, array $payload): ?NewParentLink
    {
        if (! $payload_has_parent_key) {
            return null;
        }
        return RESTNewParentLinkProxy::fromRESTPayload($payload[self::PARENT_KEY]);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildLinks(bool $payload_has_links_key, array $payload): CollectionOfForwardLinks
    {
        if (! $payload_has_links_key) {
            return new CollectionOfForwardLinks([]);
        }
        $links = array_map(
            static fn(array $link_payload) => RESTForwardLinkProxy::fromPayload($link_payload),
            $payload[self::LINKS_KEY]
        );
        return new CollectionOfForwardLinks($links);
    }
}
