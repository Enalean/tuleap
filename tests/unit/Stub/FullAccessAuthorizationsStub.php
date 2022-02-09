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

namespace Tuleap\Baseline\Stub;

use PFUser;
use Project;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\Comparison;
use Tuleap\Baseline\Domain\TransientBaseline;
use Tuleap\Baseline\Domain\TransientComparison;

class FullAccessAuthorizationsStub implements Authorizations
{
    public function canCreateBaseline(PFUser $current_user, TransientBaseline $baseline): bool
    {
        return true;
    }

    public function canDeleteBaseline(PFUser $current_user, Baseline $baseline): bool
    {
        return true;
    }

    public function canReadBaseline(PFUser $current_user, Baseline $baseline): bool
    {
        return true;
    }

    public function canReadBaselinesOnProject(PFUser $current_user, Project $project): bool
    {
        return true;
    }

    public function canCreateComparison(PFUser $current_user, TransientComparison $comparison): bool
    {
        return true;
    }

    public function canReadComparison(PFUser $current_user, Comparison $comparison): bool
    {
        return true;
    }

    public function canReadComparisonsOnProject(PFUser $current_user, Project $project): bool
    {
        return true;
    }

    public function canDeleteComparison(PFUser $current_user, Comparison $comparison)
    {
        return true;
    }
}
