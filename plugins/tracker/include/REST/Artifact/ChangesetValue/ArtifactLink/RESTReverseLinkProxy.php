<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLink;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

/**
 * @psalm-immutable
 */
final class RESTReverseLinkProxy implements ReverseLink
{
    private function __construct(private int $id, private string $type)
    {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public static function fromPayload(LinkWithDirectionRepresentation $link_payload): self
    {
        return new self($link_payload->id, self::getLinkType($link_payload));
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private static function getLinkType(LinkWithDirectionRepresentation $link_payload): string
    {
        if (! is_string($link_payload->type)) {
            throw new \Tracker_FormElement_InvalidFieldValueException(
                'Artifact links "type" must be a string, use empty string for no type'
            );
        }
        return $link_payload->type;
    }

    #[\Override]
    public function getSourceArtifactId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function getType(): string
    {
        return $this->type;
    }
}
