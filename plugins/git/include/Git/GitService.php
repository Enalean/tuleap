<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Project;
use Service;

class GitService extends Service
{
    #[\Override]
    public function getIconName(): string
    {
        return 'fas fa-tlp-versioning-git';
    }

    #[\Override]
    public function getInternationalizedName(): string
    {
        $label = $this->getLabel();

        if ($label === 'plugin_git:service_lbl_key') {
            return dgettext('tuleap-git', 'Git');
        }

        return $label;
    }

    #[\Override]
    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();

        if ($description === 'plugin_git:service_desc_key') {
            return dgettext('tuleap-git', 'Git');
        }

        return $description;
    }

    public static function getServiceUrlForProject(Project $project): string
    {
        return GIT_BASE_URL . '/' . urlencode($project->getUnixNameLowerCase());
    }

    #[\Override]
    public function getUrl(?string $url = null): string
    {
        return self::getServiceUrlForProject($this->project);
    }

    #[\Override]
    public function urlCanChange(): bool
    {
        return false;
    }
}
