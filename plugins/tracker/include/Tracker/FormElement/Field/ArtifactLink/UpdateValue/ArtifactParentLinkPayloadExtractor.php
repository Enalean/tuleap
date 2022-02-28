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

final class ArtifactParentLinkPayloadExtractor
{
    public const FIELDS_DATA_PARENT_KEY = 'parent';

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function extractParentLinkFromPayload(array $payload): ?ArtifactLink
    {
        return $this->hasPayloadAParentKey($payload) ? ArtifactLink::fromPayload($payload[self::FIELDS_DATA_PARENT_KEY]) : null;
    }

    private function hasPayloadAParentKey(array $payload): bool
    {
        return array_key_exists(self::FIELDS_DATA_PARENT_KEY, $payload);
    }
}
