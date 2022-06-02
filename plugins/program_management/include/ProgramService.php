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

class ProgramService extends \Service
{
    public function getIconName(): string
    {
        return 'fas fa-sitemap';
    }

    public function getInternationalizedName(): string
    {
        $label = $this->getLabel();

        if ($label === 'plugin_program_management:service_lbl_key') {
            return dgettext('tuleap-program_management', 'Program');
        }

        return $label;
    }

    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();

        if ($description === 'plugin_program_management:service_desc_key') {
            return dgettext('tuleap-program_management', 'Program Management');
        }

        return $description;
    }

    public function getUrl(?string $url = null): string
    {
        return sprintf('/program_management/%s', urlencode($this->project->getUnixNameLowerCase()));
    }

    public function urlCanChange(): bool
    {
        return false;
    }
}
