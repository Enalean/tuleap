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
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraServerUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\AnonymousJiraUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;

/**
 * @psalm-immutable
 */
final class JiraServerChangelogEntryValueRepresentation implements ChangelogEntryValueRepresentation
{
    /**
     * @param ChangelogEntryItemsRepresentation[] $item_representations
     */
    public function __construct(
        private int $id,
        private DateTimeImmutable $created,
        private JiraUser $changelog_owner,
        private array $item_representations,
    ) {
    }

    /**
     * @throws ChangelogAPIResponseNotWellFormedException
     */
    #[\Override]
    public static function buildFromAPIResponse(array $changelog_response): self
    {
        if (! isset($changelog_response['items'], $changelog_response['id'], $changelog_response['created'])) {
            throw new ChangelogAPIResponseNotWellFormedException();
        }

        $items = [];
        foreach ($changelog_response['items'] as $changelog_reponse_item) {
            $items[] = JiraServerChangelogEntryItemsRepresentation::buildFromAPIResponse($changelog_reponse_item);
        }

        $author = new AnonymousJiraUser();
        if (isset($changelog_response['author'])) {
            $author = ActiveJiraServerUser::buildFromPayload($changelog_response['author']);
        }

        return new self(
            (int) $changelog_response['id'],
            new DateTimeImmutable($changelog_response['created']),
            $author,
            array_filter($items),
        );
    }

    /**
     * @return ChangelogEntryItemsRepresentation[]
     */
    #[\Override]
    public function getItemRepresentations(): array
    {
        return $this->item_representations;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }

    #[\Override]
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    #[\Override]
    public function getChangelogOwner(): JiraUser
    {
        return $this->changelog_owner;
    }
}
