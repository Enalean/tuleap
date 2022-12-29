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
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Direction\ReverseLinksFeatureFlag;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

final class NewArtifactLinkChangesetValueBuilder
{
    public function __construct(private RetrieveForwardLinks $forward_links_retriever)
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
        $payload_has_all_links_key = $this->doesPayloadHaveAllLinksKey($payload);
        $payload_has_links_key     = $this->doesPayloadHaveALinksKey($payload);
        $payload_has_parent_key    = $this->doesPayloadHaveAParentKey($payload);

        $is_all_links_supported = (int) \ForgeConfig::getFeatureFlag(ReverseLinksFeatureFlag::FEATURE_FLAG_KEY) === 1;

        if ($this->isUsingAllLinksWithLink($payload_has_all_links_key, $payload_has_links_key)) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                '"all_links" key and "links" key cannot be used at the same time'
            );
        }

        if ($this->isUsingAllLinksWithParent($payload_has_all_links_key, $payload_has_parent_key)) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                '"all_links" key and "parent" key cannot be used at the same time'
            );
        }

        if (! $payload_has_all_links_key && $this->hasNotDefinedLinksOrParent($payload_has_parent_key, $payload_has_links_key)) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                '"links" and/or "parent" or "all_links" key must be defined'
            );
        }

        if ($payload_has_all_links_key && $is_all_links_supported) {
            return NewArtifactLinkChangesetValue::fromParts(
                $link_field->getId(),
                $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
                null,
                null,
                $this->buildReverse($payload)
            );
        }

        if ($this->hasNotDefinedLinksOrParent($payload_has_parent_key, $payload_has_links_key)) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                'Value should be \'links\' and an array of {"id": integer, ["type": string]} and/or \'parent\' with {"id": integer}'
            );
        }

        return NewArtifactLinkChangesetValue::fromParts(
            $link_field->getId(),
            $this->forward_links_retriever->retrieve($submitter, $link_field, $artifact),
            $this->buildFromLinksKey($payload_has_links_key, $payload),
            $this->buildParent($payload_has_parent_key, $payload),
            new CollectionOfReverseLinks([])
        );
    }

    private function doesPayloadHaveAParentKey(ArtifactValuesRepresentation $payload): bool
    {
        return is_array($payload->parent);
    }

    private function doesPayloadHaveALinksKey(ArtifactValuesRepresentation $payload): bool
    {
        return is_array($payload->links);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildParent(bool $payload_has_parent_key, ArtifactValuesRepresentation $payload): ?NewParentLink
    {
        if (! $payload_has_parent_key) {
            return null;
        }
        return RESTNewParentLinkProxy::fromRESTPayload($payload->parent);
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function buildFromLinksKey(bool $payload_has_links_key, ArtifactValuesRepresentation $payload): ?CollectionOfForwardLinks
    {
        if (! $payload_has_links_key) {
            return null;
        }

        return new CollectionOfForwardLinks(
            array_map(
                static fn(array $payload_link) => RESTForwardLinkProxy::fromPayload($payload_link),
                $payload->links
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

        return AllLinkPayloadParser::buildLinksToUpdate($payload->all_links);
    }

    private function doesPayloadHaveAllLinksKey(ArtifactValuesRepresentation $payload): bool
    {
        return is_array($payload->all_links);
    }

    private function isUsingAllLinksWithLink(bool $payload_has_all_links_key, bool $payload_has_links_key): bool
    {
        return $payload_has_all_links_key && $payload_has_links_key;
    }

    private function isUsingAllLinksWithParent(bool $payload_has_all_links_key, bool $payload_has_parent_key): bool
    {
        return $payload_has_all_links_key && $payload_has_parent_key;
    }

    private function hasNotDefinedLinksOrParent(bool $payload_has_parent_key, bool $payload_has_links_key): bool
    {
        return ! $payload_has_parent_key && ! $payload_has_links_key;
    }
}
