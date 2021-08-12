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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use PFUser;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ErrorPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;

class ConfigurationChecker
{
    /**
     * @return ErrorPresenter[]
     * @throws ProgramAccessException
     */
    public static function buildErrorsPresenter(
        BuildProgram $build_program,
        RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        \EventManager $event_manager,
        ProgramForAdministrationIdentifier $program_id,
        PFUser $user
    ): array {
        try {
            $user_identifier = UserProxy::buildFromPFUser($user);
            $program         = ProgramIdentifier::fromId($build_program, $program_id->id, $user_identifier, null);
        } catch (ProjectIsNotAProgramException $e) {
            return [];
        }

        try {
            $program_increment_tracker = $program_increment_tracker_retriever->retrieveVisibleProgramIncrementTracker(
                $program,
                $user
            );
        } catch (ProgramHasNoProgramIncrementTrackerException | ProgramTrackerNotFoundException $e) {
            return [];
        }

        $increment_errors = self::collectErrors($event_manager, $program_increment_tracker, $user);

        try {
            $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram(
                $iteration_tracker_retriever,
                $program,
                $user
            );
        } catch (ProgramTrackerNotFoundException $e) {
            return $increment_errors;
        }

        if (! $iteration_tracker) {
            return $increment_errors;
        }

        $iteration_errors = self::collectErrors($event_manager, $iteration_tracker->getFullTracker(), $user);

        return array_merge($increment_errors, $iteration_errors);
    }

    /**
     * @return ErrorPresenter[]
     */
    private static function collectErrors(\EventManager $event_manager, \Tracker $tracker, PFUser $user): array
    {
        $event = new CanSubmitNewArtifact($user, $tracker, true);
        $event_manager->dispatch($event);
        if ($event->canSubmitNewArtifact()) {
            return [];
        }

        $errors = [];
        foreach ($event->getErrorMessages() as $error_message) {
            $errors[] = new ErrorPresenter($error_message);
        }

        return $errors;
    }
}
