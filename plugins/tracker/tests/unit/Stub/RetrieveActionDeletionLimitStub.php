<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\RetrieveActionDeletionLimit;

final class RetrieveActionDeletionLimitStub implements RetrieveActionDeletionLimit
{
    private function __construct(public bool $deletion_not_allowed, public bool $limit_is_reached, public int $number_of_artifact_allowed_to_delete)
    {
    }

    public static function andThrowDeletionIsNotAllowed(): self
    {
        return new self(true, false, 10);
    }

    public static function andThrowLimitIsReached(): self
    {
        return new self(false, true, 10);
    }

    public static function retrieveRandomLimit(): self
    {
        return new self(false, false, 10);
    }

    public function getNumberOfArtifactsAllowedToDelete(PFUser $user): int
    {
        if ($this->deletion_not_allowed) {
            throw new DeletionOfArtifactsIsNotAllowedException();
        }

        if ($this->limit_is_reached) {
            throw new ArtifactsDeletionLimitReachedException();
        }

        return $this->number_of_artifact_allowed_to_delete;
    }
}
