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

use Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration\TrackerErrorPresenter;
use Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam\PotentialTeamPresenter;
use Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam\PotentialTeamsPresenterBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProgramAdmin;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProgramAdminTeam;

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
     * @var ProgramAdminTeam[]
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
    /**
     * @var ProgramSelectOptionConfigurationPresenter[]
     */
    public array $potential_iterations;
    public string $synchronize_button_label;
    public bool $has_program_increment_error;
    public bool $has_iteration_increment_error;
    public bool $has_plannable_error;
    public string $program_shortname;
    public ?string $program_increment_label;
    public ?string $program_increment_sub_label;
    public ?string $iteration_label;
    public ?string $iteration_sub_label;
    public ?TrackerErrorPresenter $program_increment_error_presenter;
    public ?TrackerErrorPresenter $iteration_error_presenter;
    public ?TrackerErrorPresenter $plannable_error_presenter;
    public bool $is_project_used_in_plan;
    public string $project_team_access_errors;

    private function __construct(ProgramAdmin $program_admin)
    {
        $this->program_id                        = $program_admin->program->id;
        $this->program_shortname                 = $program_admin->program_shortname;
        $this->program_increment_label           = $program_admin->program_increment_label;
        $this->program_increment_sub_label       = $program_admin->program_increment_sub_label;
        $this->iteration_label                   = $program_admin->iteration_label;
        $this->iteration_sub_label               = $program_admin->iteration_sub_label;
        $this->aggregated_teams                  = $program_admin->aggregated_teams;
        $this->has_aggregated_teams              = count($program_admin->aggregated_teams) > 0;
        $this->is_project_used_in_plan           = $program_admin->is_project_used_in_plan;
        $this->potential_program_increments      = ProgramSelectOptionConfigurationPresenter::build($program_admin->potential_program_increments);
        $this->potential_plannable_trackers      = ProgramSelectOptionConfigurationPresenter::build($program_admin->potential_plannable_trackers);
        $this->ugroups_can_prioritize            = ProgramSelectOptionConfigurationPresenter::build($program_admin->ugroups_can_prioritize);
        $this->potential_iterations              = ProgramSelectOptionConfigurationPresenter::build($program_admin->potential_iterations);
        $this->program_increment_error_presenter = TrackerErrorPresenter::fromTrackerError($program_admin->program_increment_error);
        $this->iteration_error_presenter         = TrackerErrorPresenter::fromTrackerError($program_admin->iteration_error);
        $this->plannable_error_presenter         = TrackerErrorPresenter::fromTrackerError($program_admin->plannable_error);
        $this->potential_teams                   = PotentialTeamsPresenterBuilder::buildPotentialTeamsPresenter($program_admin->potential_teams);

        $this->has_errors                    = $program_admin->has_presenter_errors;
        $this->has_program_increment_error   = $program_admin->has_program_increment_error;
        $this->has_iteration_increment_error = $program_admin->has_iteration_error;
        $this->has_plannable_error           = $program_admin->has_plannable_error;
        if ($program_admin->program_increment_sub_label) {
            $this->synchronize_button_label = sprintf(dgettext('tuleap-program_management', "Sync open %s"), $program_admin->program_increment_sub_label);
        } else {
            $this->synchronize_button_label = dgettext('tuleap-program_management', "Sync open Program Increments");
        }

        $this->project_team_access_errors = $program_admin->project_team_access_errors;
    }

    public static function build(ProgramAdmin $program_admin): self
    {
        return new self($program_admin);
    }
}
