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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Value\ArtifactLink;

use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue\Link;

/**
 * @psalm-immutable
 */
final class RESTLinkProxy implements Link
{
    private const PAYLOAD_KEY_ID   = 'id';
    private const PAYLOAD_KEY_TYPE = 'type';

    private function __construct(private int $id, private ?string $type)
    {
    }

    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public static function fromPayload(array $link_payload): self
    {
        self::checkLinkPayloadStructure($link_payload);

        return new self(
            (int) $link_payload[self::PAYLOAD_KEY_ID],
            self::getLinkType($link_payload)
        );
    }

    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    private static function checkLinkPayloadStructure(array $link_payload): void
    {
        if (! array_key_exists(self::PAYLOAD_KEY_ID, $link_payload)) {
            throw new Tracker_FormElement_InvalidFieldValueException(
                'Artifact links must have an id'
            );
        }
    }

    private static function getLinkType(array $link_payload): ?string
    {
        if (! array_key_exists(self::PAYLOAD_KEY_TYPE, $link_payload) || ! is_string($link_payload[self::PAYLOAD_KEY_TYPE])) {
            return null;
        }

        return $link_payload[self::PAYLOAD_KEY_TYPE];
    }

    public function getTargetArtifactId(): int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
