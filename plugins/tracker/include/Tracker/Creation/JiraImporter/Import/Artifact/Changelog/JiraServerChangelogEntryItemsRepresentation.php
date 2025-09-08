<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

/**
 * @psalm-immutable
 */
class JiraServerChangelogEntryItemsRepresentation implements ChangelogEntryItemsRepresentation
{
    public function __construct(
        private string $field,
        private ?string $from,
        private ?string $from_string,
        private ?string $to,
        private ?string $toString,
    ) {
    }

    /**
     * @throws ChangelogAPIResponseNotWellFormedException
     */
    #[\Override]
    public static function buildFromAPIResponse(array $response): ?self
    {
        if (! array_key_exists('field', $response)) {
            return null;
        }

        if (
            ! array_key_exists('from', $response) ||
            ! array_key_exists('fromString', $response) ||
            ! array_key_exists('to', $response) ||
            ! array_key_exists('toString', $response)
        ) {
            throw new ChangelogAPIResponseNotWellFormedException();
        }

        return new self(
            $response['field'],
            $response['from'],
            $response['fromString'],
            $response['to'],
            $response['toString'],
        );
    }

    #[\Override]
    public function getFieldId(): string
    {
        return $this->field;
    }

    #[\Override]
    public function getFrom(): ?string
    {
        return $this->from;
    }

    #[\Override]
    public function getFromString(): ?string
    {
        return $this->from_string;
    }

    #[\Override]
    public function getTo(): ?string
    {
        return $this->to;
    }

    #[\Override]
    public function getToString(): ?string
    {
        return $this->toString;
    }
}
