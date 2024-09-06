<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Sidebar;

use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectAccessSuspendedException;

/**
 * I am a parent or child project, linked to another project.
 * I exist to avoid passing around fat \Project instances when we don't need them.
 * @psalm-immutable
 */
final readonly class LinkedProject
{
    private function __construct(
        public string $public_name,
        public string $uri,
        public string $project_icon,
        public int $id,
    ) {
    }

    public static function fromProject(CheckProjectAccess $access_checker, \Project $project, \PFUser $user): ?self
    {
        try {
            $access_checker->checkUserCanAccessProject($user, $project);
        } catch (ProjectAccessSuspendedException | \Project_AccessDeletedException | \Project_AccessPrivateException | \Project_AccessRestrictedException | \Project_AccessProjectNotFoundException) {
            return null;
        }
        return new self(
            $project->getPublicName(),
            $project->getUrl(),
            EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint()),
            (int) $project->getID(),
        );
    }
}
