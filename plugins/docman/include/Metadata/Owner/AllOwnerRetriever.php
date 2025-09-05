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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use Project;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\BuildDisplayName;
use Tuleap\User\ProvideUserFromRow;

final class AllOwnerRetriever implements RetrieveAllOwner
{
    public function __construct(
        private OwnerData $owner_dao,
        private ProvideUserFromRow $user_manager,
        private BuildDisplayName $user_helper,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    /**
     * @return OwnerRepresentationForAutocomplete[]
     */
    #[\Override]
    public function retrieveProjectDocumentOwnersForAutocomplete(Project $project, string $name_to_search): array
    {
        $owners_rows = $this->owner_dao->getDocumentOwnerOfProjectForAutocomplete($project, $name_to_search);

        if (! isset($owners_rows)) {
            return [];
        }

        $project_document_owner = [];
        foreach ($owners_rows as $row) {
            $user                     = $this->user_manager->getUserInstanceFromRow($row);
            $owner                    = OwnerRepresentationForAutocomplete::buildForSelect2AutocompleteFromOwner($user, $this->user_helper, $this->provide_user_avatar_url);
            $project_document_owner[] = $owner;
        }

        return $project_document_owner;
    }
}
