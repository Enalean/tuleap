<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration\Admin;

use Tuleap\MediawikiStandalone\Instance\OngoingInitializationStatus;
use Tuleap\Project\ProjectPresenter;

final class LegacyReadyToMigratePresenter
{
    public readonly bool $can_perform_migration;

    public function __construct(
        public readonly ProjectPresenter $project,
        public readonly bool $is_ongoing_initialization,
        public readonly bool $is_error,
        public readonly bool $is_allowed,
    ) {
        $this->can_perform_migration = $this->is_allowed && ! $this->is_ongoing_initialization && ! $this->is_error;
    }

    public static function fromProject(
        \Project $project,
        OngoingInitializationStatus $initialization_status,
        \PFUser $current_user,
        bool $is_allowed,
    ): self {
        return new self(
            ProjectPresenter::fromProject($project, $current_user),
            $initialization_status !== OngoingInitializationStatus::None,
            $initialization_status === OngoingInitializationStatus::InError,
            $is_allowed,
        );
    }
}
