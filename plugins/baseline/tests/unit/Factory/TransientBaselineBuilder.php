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

namespace Tuleap\Baseline\Factory;

use DateTimeInterface;
use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\Baseline\Domain\TransientBaseline;

class TransientBaselineBuilder
{
    /** @var string */
    private $name;

    /** @var BaselineArtifact */
    private $artifact;

    /** @var DateTimeInterface|null */
    private $snapshot_date;

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function artifact(BaselineArtifact $artifact): self
    {
        $this->artifact = $artifact;
        return $this;
    }

    public function snapshotDate(?DateTimeInterface $snapshot_date): self
    {
        $this->snapshot_date = $snapshot_date;
        return $this;
    }

    public function build(): TransientBaseline
    {
        return new TransientBaseline($this->name, $this->artifact, $this->snapshot_date);
    }
}
