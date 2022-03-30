<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Domain;

use DateTimeInterface;
use Project;

class TransientBaseline
{
    /** @var string */
    private $name;

    /** @var BaselineArtifact */
    private $artifact;

    /** @var DateTimeInterface|null */
    private $snapshot_date;

    public function __construct(string $name, BaselineArtifact $artifact, ?DateTimeInterface $snapshot_date)
    {
        $this->name          = $name;
        $this->artifact      = $artifact;
        $this->snapshot_date = $snapshot_date;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArtifact(): BaselineArtifact
    {
        return $this->artifact;
    }

    public function getSnapshotDate(): ?DateTimeInterface
    {
        return $this->snapshot_date;
    }

    public function getProject(): Project
    {
        return $this->artifact->getProject();
    }
}
