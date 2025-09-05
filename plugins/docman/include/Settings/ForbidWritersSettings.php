<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Settings;

final class ForbidWritersSettings implements ITellIfWritersAreAllowedToUpdatePropertiesOrDelete
{
    public function __construct(private ForbidWritersDAOSettings $dao)
    {
    }

    #[\Override]
    public function areWritersAllowedToUpdateProperties(int $project_id): bool
    {
        $settings = $this->dao->searchByProjectId($project_id);

        if (! $settings) {
            return true;
        }

        return ! $settings['forbid_writers_to_update'];
    }

    #[\Override]
    public function areWritersAllowedToDelete(int $project_id): bool
    {
        $settings = $this->dao->searchByProjectId($project_id);

        if (! $settings) {
            return true;
        }

        return ! $settings['forbid_writers_to_delete'];
    }
}
