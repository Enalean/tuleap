<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use Tuleap\User\UserEmailCollection;

final class OrganizeableGitCrossReferencesAndTheContributors
{
    /**
     * @var CommitDetailsCrossReferenceInformation[]
     */
    private $organizeable_cross_references_information_collection;
    /**
     * @var UserEmailCollection
     */
    private $contributors_email_collection;

    /**
     * @param CommitDetailsCrossReferenceInformation[] $organizeable_cross_references_information_collection
     */
    public function __construct(
        array $organizeable_cross_references_information_collection,
        UserEmailCollection $contributors_email_collection,
    ) {
        $this->organizeable_cross_references_information_collection = $organizeable_cross_references_information_collection;
        $this->contributors_email_collection                        = $contributors_email_collection;
    }

    /**
     * @return CommitDetailsCrossReferenceInformation[]
     */
    public function getOrganizeableCrossReferencesInformationCollection(): array
    {
        return $this->organizeable_cross_references_information_collection;
    }

    public function getContributorsEmailCollection(): UserEmailCollection
    {
        return $this->contributors_email_collection;
    }
}
