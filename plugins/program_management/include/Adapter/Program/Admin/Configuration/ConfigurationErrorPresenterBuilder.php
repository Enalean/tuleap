<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TrackerErrorPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ConfigurationErrorPresenterBuilder
{
    private ConfigurationErrorsGatherer $errors_gatherer;

    public function __construct(ConfigurationErrorsGatherer $errors_gatherer)
    {
        $this->errors_gatherer = $errors_gatherer;
    }

    public function buildProgramIncrementErrorPresenter(
        ProgramTracker $program_increment_tracker,
        ?ProgramIdentifier $program,
        \PFUser $user,
        ConfigurationErrorsCollector $errors_collector
    ): ?TrackerErrorPresenter {
        if (! $program) {
            return null;
        }

        return $this->buildTrackerErrorPresenter($program_increment_tracker, UserProxy::buildFromPFUser($user), $errors_collector);
    }

    public function buildIterationErrorPresenter(
        ?ProgramTracker $iteration_tracker,
        \PFUser $user,
        ConfigurationErrorsCollector $errors_collector
    ): ?TrackerErrorPresenter {
        if (! $iteration_tracker) {
            return null;
        }

        return $this->buildTrackerErrorPresenter($iteration_tracker, UserProxy::buildFromPFUser($user), $errors_collector);
    }

    private function buildTrackerErrorPresenter(
        ProgramTracker $program_tracker,
        UserIdentifier $user_identifier,
        ConfigurationErrorsCollector $errors_collector
    ): ?TrackerErrorPresenter {
        return TrackerErrorPresenter::fromTracker(
            $this->errors_gatherer,
            $program_tracker,
            $user_identifier,
            $errors_collector
        );
    }
}
