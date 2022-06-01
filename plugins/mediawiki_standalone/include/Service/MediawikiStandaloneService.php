<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\Project\Service\ServiceForCreation;

final class MediawikiStandaloneService extends \Service implements ServiceForCreation
{
    private const ICON_NAME          = 'fas fa-tlp-mediawiki';
    private const SERVICE_URL_PREFIX = '/mediawiki/';
    public const  SERVICE_SHORTNAME  = 'plugin_mediawiki_standalone';

    public static function forServiceCreation(\Project $project): self
    {
        return new self(
            $project,
            [
                'service_id' => self::FAKE_ID_FOR_CREATION,
                'group_id' => $project->getID(),
                'label' => 'MediaWiki',
                'description' => '',
                'short_name' => self::SERVICE_SHORTNAME,
                'link' => '#',
                'is_active' => 1,
                'is_used' => 0,
                'scope' => self::SCOPE_SYSTEM,
                'rank' => 161,
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
        return 'fas fa-tlp-mediawiki';
    }

    public function getInternationalizedName(): string
    {
        return 'MediaWiki';
    }

    public function getProjectAdministrationName(): string
    {
        return dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone');
    }

    public function getInternationalizedDescription(): string
    {
        return $this->getProjectAdministrationName();
    }

    public function getUrl(?string $url = null): string
    {
        return self::SERVICE_URL_PREFIX . $this->project->getUnixNameLowerCase();
    }

    public function urlCanChange(): bool
    {
        return false;
    }
}
