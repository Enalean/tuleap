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

use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;

final class ArtifactLinksPayloadExtractor
{
    public const FIELDS_DATA_LINKS_KEY = 'links';

    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public function extractValuesFromPayload(array $payload): ?CollectionOfForwardLinks
    {
        if (! $this->hasPayloadALinksKey($payload)) {
            return null;
        }

        return new CollectionOfForwardLinks(array_map(
            static fn(array $payload_link) => RESTForwardLinkProxy::fromPayload($payload_link),
            $payload[self::FIELDS_DATA_LINKS_KEY]
        ));
    }

    private function hasPayloadALinksKey(array $payload): bool
    {
        return array_key_exists(self::FIELDS_DATA_LINKS_KEY, $payload);
    }
}
