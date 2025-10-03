<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1\ArtifactSection;

use DateTimeImmutable;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final readonly class ArtifactVersionRepresentationBuilder
{
    public function __construct(
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private RetrieveUserById $retrieve_user_by_id,
    ) {
    }

    /**
     * @param list<\Tracker_Artifact_Changeset> $changesets
     * @return list<ArtifactVersionRepresentation>
     */
    public function build(array $changesets): array
    {
        $representations = [];

        foreach ($changesets as $changeset) {
            $author = $this->retrieve_user_by_id->getUserById($changeset->getSubmittedBy());
            if ($author === null) {
                continue;
            }

            $representations[] = new ArtifactVersionRepresentation(
                (int) $changeset->getId(),
                new DateTimeImmutable()->setTimestamp((int) $changeset->getSubmittedOn())->format(\DateTime::ATOM),
                MinimalUserRepresentation::build($author, $this->provide_user_avatar_url),
            );
        }

        return $representations;
    }
}
