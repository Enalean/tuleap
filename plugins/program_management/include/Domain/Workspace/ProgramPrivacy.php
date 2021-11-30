<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Workspace;

/**
 * @psalm-immutable
 */
final class ProgramPrivacy
{
    public function __construct(
        public bool $are_restricted_users_allowed,
        public bool $project_is_public_incl_restricted,
        public bool $project_is_private,
        public bool $project_is_public,
        public bool $project_is_private_incl_restricted,
        public string $explanation_text,
        public string $privacy_title,
        public string $project_name,
    ) {
    }

    public static function fromPrivacy(
        bool $are_restricted_users_allowed,
        bool $project_is_public_incl_restricted,
        bool $project_is_private,
        bool $project_is_public,
        bool $project_is_private_incl_restricted,
        string $explanation_text,
        string $privacy_title,
        string $project_name,
    ): self {
        return new self(
            $are_restricted_users_allowed,
            $project_is_public_incl_restricted,
            $project_is_private,
            $project_is_public,
            $project_is_private_incl_restricted,
            $explanation_text,
            $privacy_title,
            $project_name
        );
    }
}
