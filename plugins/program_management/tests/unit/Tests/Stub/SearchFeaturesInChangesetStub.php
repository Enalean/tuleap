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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchFeaturesInChangeset;

final class SearchFeaturesInChangesetStub implements SearchFeaturesInChangeset
{
    private function __construct(private array $changesets_and_features)
    {
    }

    public static function build(): self
    {
        return new self([]);
    }

    /**
     * @param int[] $ids_of_features_in_changeset
     */
    public function withChangesetsAndFeatures(ChangesetIdentifier $changeset, array $ids_of_features_in_changeset): self
    {
        $this->changesets_and_features[$changeset->getId()] = $ids_of_features_in_changeset;
        return $this;
    }

    #[\Override]
    public function getArtifactsLinkedInChangeset(ChangesetIdentifier $changeset_identifier): array
    {
        if (! isset($this->changesets_and_features[$changeset_identifier->getId()])) {
            return [];
        }

        return $this->changesets_and_features[$changeset_identifier->getId()];
    }
}
