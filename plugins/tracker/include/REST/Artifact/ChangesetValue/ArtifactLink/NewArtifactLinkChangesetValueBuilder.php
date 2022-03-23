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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\RetrieveForwardLinks;

final class NewArtifactLinkChangesetValueBuilder
{
    private const PARENT_KEY = 'parent';
    private const LINKS_KEY  = 'links';

    public function __construct(
        private RetrieveForwardLinks $forward_links_retriever,
    ) {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function buildFromPayload(
        Artifact $artifact,
        \Tracker_FormElement_Field_ArtifactLink $link_field,
        \PFUser $submitter,
        array $payload,
    ): NewArtifactLinkChangesetValue {
        $payload_has_parent_key = $this->doesPayloadHaveAParentKey($payload);
        $payload_has_links_key  = $this->doesPayloadHaveALinksKey($payload);

        if (! $payload_has_parent_key && ! $payload_has_links_key) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                'Value should be \'links\' and an array of {"id": integer, ["type": string]} and/or \'parent\' with {"id": integer}'
            );
        }

        return NewArtifactLinkChangesetValue::fromParts(
            $link_field->getId(),
            $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
            $this->buildFromLinksKey($payload_has_links_key, $payload),
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
    private function buildFromLinksKey(bool $payload_has_links_key, array $payload): ?CollectionOfForwardLinks
    {
        if (! $payload_has_links_key) {
            return null;
        }

        return new CollectionOfForwardLinks(
            array_map(
                static fn(array $payload_link) => RESTForwardLinkProxy::fromPayload($payload_link),
                $payload[self::LINKS_KEY]
            )
        );
    }
}
