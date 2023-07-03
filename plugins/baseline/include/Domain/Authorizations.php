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

interface Authorizations
{
    public function canCreateBaseline(UserIdentifier $current_user, TransientBaseline $baseline): bool;

    public function canDeleteBaseline(UserIdentifier $current_user, Baseline $baseline): bool;

    public function canReadBaseline(UserIdentifier $current_user, Baseline $baseline): bool;

    public function canReadBaselinesOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool;

    public function canCreateComparison(UserIdentifier $current_user, TransientComparison $comparison): bool;

    public function canDeleteComparison(UserIdentifier $current_user, Comparison $comparison): bool;

    public function canReadComparison(UserIdentifier $current_user, Comparison $comparison): bool;

    public function canReadComparisonsOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool;

    public function canUserAdministrateBaselineOnProject(UserIdentifier $current_user, ProjectIdentifier $project): bool;
}
