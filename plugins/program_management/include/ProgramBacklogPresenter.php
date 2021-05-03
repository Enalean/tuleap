<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Tuleap\Project\Flags\ProjectFlagPresenter;
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
    /**
     * @var string
     */
    public $project_short_name;
    /**
     * @var string
     */
    public $project_privacy;
    /**
     * @var string
     */
    public $project_flags;
    /**
     * @var int|null
     */
    public $program_id;
    /**
     * @var bool
     */
    public $user_has_accessibility_mode;
    /**
     * @var bool
     */
    public $can_create_program_increment;
    /**
     * @var int
     */
    public $program_increment_tracker_id;
    /**
     * @var string
     */
    public $program_increment_label;
    /**
     * @var string
     */
    public $program_increment_sub_label;

    /**
     * @param ProjectFlagPresenter[] $project_flags
     */
    public function __construct(
        \Project $project,
        array $project_flags,
        bool $user_has_accessibility_mode,
        bool $can_create_program_increment,
        int $program_increment_tracker_id,
        ?string $program_increment_label,
        ?string $program_increment_sub_label
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
        $this->can_create_program_increment = $can_create_program_increment;
        $this->program_increment_tracker_id = $program_increment_tracker_id;
        $this->program_increment_label      = dgettext('tuleap-program_management', "Program Increments");
        $this->program_increment_sub_label  = dgettext('tuleap-program_management', "program increment");

        if ($program_increment_label) {
            $this->program_increment_label = $program_increment_label;
        }

        if ($program_increment_sub_label) {
            $this->program_increment_sub_label = $program_increment_sub_label;
        }
    }
}
