<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Stub\FormElement\Burnup\Claculator;

use Tuleap\AgileDashboard\FormElement\Burnup\Calculator\RetrieveBurnupEffortForArtifact;
use Tuleap\AgileDashboard\FormElement\BurnupEffort;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class RetrieveBurnupEffortForArtifactStub implements RetrieveBurnupEffortForArtifact
{
    private function __construct(private readonly BurnupEffort $burnup_effort)
    {
    }

    public static function withEffort(BurnupEffort $effort): self
    {
        return new self($effort);
    }

    public function getEffort(Artifact $artifact, int $timestamp): BurnupEffort
    {
        return $this->burnup_effort;
    }
}
