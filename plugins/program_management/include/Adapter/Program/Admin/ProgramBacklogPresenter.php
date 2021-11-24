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

use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;

/**
 * @psalm-immutable
 */
final class ProgramBacklogPresenter
{
    /**
     * @var mixed
     */
    public $project_name;
    public string $project_short_name;
    public string $project_privacy;
    public string $project_flags;
    public ?int $program_id;
    public bool $user_has_accessibility_mode;
    public bool $can_create_program_increment;
    public int $program_increment_tracker_id;
    public string $program_increment_label;
    public string $program_increment_sub_label;
    public bool $is_program_admin;
    public bool $is_configured;
    public bool $has_plan_permissions;
    public string $project_icon;
    public string $iteration_label;

    public function __construct(
        \Project $project,
        array $project_flags,
        bool $user_has_accessibility_mode,
        ProgramBacklogConfigurationPresenter $backlog_configuration,
        bool $is_program_admin
    ) {
        $this->project_name                 = $project->getPublicName();
        $this->project_short_name           = $project->getUnixNameLowerCase();
        $this->project_privacy              = json_encode(
            ProjectPrivacyPresenter::fromProject($project),
            JSON_THROW_ON_ERROR
        );
        $this->project_flags                = json_encode($project_flags, JSON_THROW_ON_ERROR);
        $this->program_id                   = (int) $project->getID();
        $this->user_has_accessibility_mode  = $user_has_accessibility_mode;
        $this->can_create_program_increment = $backlog_configuration->can_create_program;
        $this->has_plan_permissions         = $backlog_configuration->has_plan_permissions;
        $this->program_increment_tracker_id = $backlog_configuration->program_increment_tracker_id;
        $this->program_increment_label      = dgettext('tuleap-program_management', "Program Increments");
        $this->program_increment_sub_label  = dgettext('tuleap-program_management', "program increment");

        if ($backlog_configuration->program_increment_label) {
            $this->program_increment_label = $backlog_configuration->program_increment_label;
        }

        if ($backlog_configuration->program_increment_sublabel) {
            $this->program_increment_sub_label = $backlog_configuration->program_increment_sublabel;
        }

        $this->iteration_label = dgettext('tuleap-program_management', "Iterations");

        if ($backlog_configuration->iteration_label) {
            $this->iteration_label = $backlog_configuration->iteration_label;
        }

        $this->is_program_admin = $is_program_admin;
        $this->is_configured    = $backlog_configuration->is_configured;
        $this->project_icon     = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());
    }
}
