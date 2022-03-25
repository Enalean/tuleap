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

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;

/**
 * @psalm-immutable
 */
final class RESTNewParentLinkProxy implements NewParentLink
{
    private const ID_KEY = 'id';

    private function __construct(private int $id)
    {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public static function fromRESTPayload(array $payload): self
    {
        if (! array_key_exists(self::ID_KEY, $payload)) {
            throw new \Tracker_FormElement_InvalidFieldValueException("Parent must have an 'id' key");
        }
        if (! is_int($payload[self::ID_KEY])) {
            throw new \Tracker_FormElement_InvalidFieldValueException("Parent 'id' key must be an integer");
        }

        return new self($payload[self::ID_KEY]);
    }

    public function getParentArtifactId(): int
    {
        return $this->id;
    }
}
