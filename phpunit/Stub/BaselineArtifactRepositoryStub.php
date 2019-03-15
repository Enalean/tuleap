<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\Stub;

use DateTime;
use Exception;
use PFUser;
use Tuleap\Baseline\BaselineArtifact;
use Tuleap\Baseline\BaselineArtifactRepository;

/**
 * In memory implementation of BaselineArtifactRepository used for tests
 */
class BaselineArtifactRepositoryStub implements BaselineArtifactRepository
{
    /** @var BaselineArtifact[] */
    private $artifacts_by_id = [];

    public function add(BaselineArtifact $artifact): void
    {
        $this->artifacts_by_id [$artifact->getId()] = $artifact;
    }

    public function findById(PFUser $current_user, int $id): ?BaselineArtifact
    {
        return $this->artifacts_by_id[$id] ?? null;
    }

    public function removeAll(): void
    {
        $this->artifacts_by_id = [];
    }

    public function findAt(PFUser $current_user, BaselineArtifact $artifact, DateTime $date): ?BaselineArtifact
    {
        throw new Exception("Method findAt not implemented yet");
    }
}
