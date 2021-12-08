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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin;

use Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam\PotentialTeamPresenter;
use Tuleap\ProgramManagement\Adapter\Program\Admin\Team\TeamPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TrackerErrorPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramAdminPresenter
{
    public int $program_id;
    /**
     * @var PotentialTeamPresenter[]
     */
    public array $potential_teams;
    /**
     * @var TeamPresenter[]
     */
    public array $aggregated_teams;
    public bool $has_aggregated_teams;
    public bool $has_errors;
    /**
     * @var ProgramSelectOptionConfigurationPresenter[]
     */
    public array $potential_program_increments;
    /**
     * @var ProgramSelectOptionConfigurationPresenter[]
     */
    public array $potential_plannable_trackers;
    /**
     * @var ProgramSelectOptionConfigurationPresenter[]
     */
    public array $ugroups_can_prioritize;
    public ?string $program_increment_label;
    public ?string $program_increment_sub_label;
    /**
     * @var ProgramSelectOptionConfigurationPresenter[]
     */
    public array $potential_iterations;
    public ?string $iteration_label;
    public ?string $iteration_sub_label;
    public ?TrackerErrorPresenter $program_increment_error_presenter;
    public ?TrackerErrorPresenter $iteration_error_presenter;
    public ?TrackerErrorPresenter $plannable_error_presenter;


    /**
     * @param PotentialTeamPresenter[]                    $potential_teams
     * @param TeamPresenter[]                             $aggregated_teams
     * @param ProgramSelectOptionConfigurationPresenter[] $potential_program_increments
     * @param ProgramSelectOptionConfigurationPresenter[] $potential_plannable_trackers
     * @param ProgramSelectOptionConfigurationPresenter[] $ugroups_can_prioritize
     * @param ProgramSelectOptionConfigurationPresenter[] $potential_iterations
     */
    public function __construct(
        ProgramForAdministrationIdentifier $program,
        array $potential_teams,
        array $aggregated_teams,
        array $potential_program_increments,
        array $potential_plannable_trackers,
        array $ugroups_can_prioritize,
        ?string $program_increment_label,
        ?string $program_increment_sub_label,
        array $potential_iterations,
        ?string $iteration_label,
        ?string $iteration_sub_label,
        ?TrackerErrorPresenter $program_increment_error_presenter,
        ?TrackerErrorPresenter $iteration_error_presenter,
        ?TrackerErrorPresenter $plannable_error_presenter,
    ) {
        $this->program_id                   = $program->id;
        $this->potential_teams              = $potential_teams;
        $this->aggregated_teams             = $aggregated_teams;
        $this->has_aggregated_teams         = count($aggregated_teams) > 0;
        $this->potential_program_increments = $potential_program_increments;
        $this->potential_plannable_trackers = $potential_plannable_trackers;
        $this->ugroups_can_prioritize       = $ugroups_can_prioritize;
        $this->program_increment_label      = $program_increment_label;
        $this->program_increment_sub_label  = $program_increment_sub_label;
        $this->potential_iterations         = $potential_iterations;
        $this->iteration_label              = $iteration_label;
        $this->iteration_sub_label          = $iteration_sub_label;

        $this->has_errors                        =  ($program_increment_error_presenter && $program_increment_error_presenter->has_presenter_errors)
            || ($iteration_error_presenter && $iteration_error_presenter->has_presenter_errors)
            || ($plannable_error_presenter && $plannable_error_presenter->has_presenter_errors);
        $this->program_increment_error_presenter = $program_increment_error_presenter;
        $this->iteration_error_presenter         = $iteration_error_presenter;
        $this->plannable_error_presenter         = $plannable_error_presenter;
    }
}
