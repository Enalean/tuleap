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

namespace Tuleap\Baseline\Adapter;

use DateTime;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tuleap\Baseline\ChangesetRepository;

class ChangesetRepositoryAdapter implements ChangesetRepository
{
    /** @var Tracker_Artifact_ChangesetFactory */
    private $changeset_factory;

    public function __construct(Tracker_Artifact_ChangesetFactory $changeset_factory)
    {
        $this->changeset_factory = $changeset_factory;
    }

    public function findByArtifactAndDate(
        Tracker_Artifact $artifact,
        DateTime $date
    ): ?Tracker_Artifact_Changeset {
        return $this->changeset_factory->getChangesetAtTimestamp(
            $artifact,
            $date->getTimestamp()
        );
    }
}
