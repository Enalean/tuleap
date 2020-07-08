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

use DateTimeImmutable;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\JiraUser;

/**
 * @psalm-immutable
 */
class ChangelogEntryValueRepresentation
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var ChangelogEntryItemsRepresentation[]
     */
    private $item_representations;

    /**
     * @var DateTimeImmutable
     */
    private $created;

    /**
     * @var JiraUser
     */
    private $changelog_owner;

    public function __construct(
        int $id,
        DateTimeImmutable $created,
        JiraUser $changelog_owner,
        array $item_representations
    ) {
        $this->id                   = $id;
        $this->item_representations = $item_representations;
        $this->created              = $created;
        $this->changelog_owner      = $changelog_owner;
    }

    /**
     * @throws ChangelogAPIResponseNotWellFormedException
     */
    public static function buildFromAPIResponse(array $changelog_response): self
    {
        if (
            ! array_key_exists('items', $changelog_response) ||
            ! array_key_exists('id', $changelog_response) ||
            ! array_key_exists('created', $changelog_response)
        ) {
            throw new ChangelogAPIResponseNotWellFormedException();
        }

        $items = [];
        foreach ($changelog_response['items'] as $changelog_reponse_item) {
            $items[] = ChangelogEntryItemsRepresentation::buildFromAPIResponse($changelog_reponse_item);
        }

        return new self(
            (int) $changelog_response['id'],
            new DateTimeImmutable($changelog_response['created']),
            new JiraUser($changelog_response['author']),
            array_filter($items),
        );
    }

    public function getItemRepresentations(): array
    {
        return $this->item_representations;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getChangelogOwner(): JiraUser
    {
        return $this->changelog_owner;
    }
}
