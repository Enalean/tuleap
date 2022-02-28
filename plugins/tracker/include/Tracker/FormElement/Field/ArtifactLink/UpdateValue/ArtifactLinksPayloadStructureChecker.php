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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use Tracker_FormElement_InvalidFieldValueException;

final class ArtifactLinksPayloadStructureChecker
{
    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public function checkPayloadStructure(array $payload): void
    {
        if (
            ! $this->hasPayloadAParentKey($payload) &&
            ! $this->hasPayloadALinksKey($payload)
        ) {
            throw new Tracker_FormElement_InvalidFieldValueException(
                'Value should be \'links\' and an array of {"id": integer, ["type": string]} and/or \'parent\' with {"id": integer}'
            );
        }
    }

    private function hasPayloadAParentKey(array $payload): bool
    {
        return array_key_exists(ArtifactParentLinkPayloadExtractor::FIELDS_DATA_PARENT_KEY, $payload);
    }

    private function hasPayloadALinksKey(array $payload): bool
    {
        return array_key_exists(
            ArtifactLinksPayloadExtractor::FIELDS_DATA_LINKS_KEY,
            $payload
        ) && is_array($payload[ArtifactLinksPayloadExtractor::FIELDS_DATA_LINKS_KEY]);
    }
}
