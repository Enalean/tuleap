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
class ChangelogEntryItemsRepresentation
{
    /**
     * @var string
     */
    private $field_id;

    /**
     * @var string|null
     */
    private $from;

    /**
     * @var string|null
     */
    private $from_string;

    /**
     * @var string|null
     */
    private $to;

    /**
     * @var string|null
     */
    private $toString;

    public function __construct(
        string $field_id,
        ?string $from,
        ?string $from_string,
        ?string $to,
        ?string $toString
    ) {
        $this->field_id    = $field_id;
        $this->from        = $from;
        $this->from_string = $from_string;
        $this->to          = $to;
        $this->toString    = $toString;
    }

    /**
     * @throws ChangelogAPIResponseNotWellFormedException
     */
    public static function buildFromAPIResponse(array $response): ?self
    {
        if (! array_key_exists('fieldId', $response)) {
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
            $response['fieldId'],
            $response['from'],
            $response['fromString'],
            $response['to'],
            $response['toString'],
        );
    }

    public function getFieldId(): string
    {
        return $this->field_id;
    }

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function getFromString(): ?string
    {
        return $this->from_string;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function getToString(): ?string
    {
        return $this->toString;
    }
}
