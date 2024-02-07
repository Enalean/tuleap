<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

/**
 * @psalm-immutable
 */
final class TrackerCreationSettings
{
    public function __construct(
        private bool $is_displayed_in_new_dropdown,
        private bool $is_private_comment_used,
        private bool $enable_move_artifacts,
    ) {
    }

    public function isDisplayedInNewDropdown(): bool
    {
        return $this->is_displayed_in_new_dropdown;
    }

    public function isPrivateCommentUsed(): bool
    {
        return $this->is_private_comment_used;
    }

    public function isMoveArtifactsEnabled(): bool
    {
        return $this->enable_move_artifacts;
    }
}
