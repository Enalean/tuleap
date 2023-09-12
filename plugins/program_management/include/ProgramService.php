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

use Tuleap\Project\Service\ServiceForCreation;

class ProgramService extends \Service implements ServiceForCreation
{
    private const ICON_NAME         = 'fas fa-sitemap';
    public const  SERVICE_SHORTNAME = 'plugin_program_management';

    public static function forServiceCreation(\Project $project): self
    {
        return new self(
            $project,
            [
                'service_id' => self::FAKE_ID_FOR_CREATION,
                'group_id' => $project->getID(),
                'label' => 'plugin_program_management:service_lbl_key',
                'description' => 'plugin_program_management:service_desc_key',
                'short_name' => self::SERVICE_SHORTNAME,
                'link' => '#',
                'is_active' => 1,
                'is_used' => 0,
                'scope' => self::SCOPE_SYSTEM,
                'rank' => 153,
                'location' => '',
                'server_id' => null,
                'is_in_iframe' => 0,
                'is_in_new_tab' => false,
                'icon' => self::ICON_NAME,
            ],
        );
    }

    public function getIconName(): string
    {
        return self::ICON_NAME;
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
