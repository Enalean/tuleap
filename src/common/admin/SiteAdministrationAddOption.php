<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Admin;

final class SiteAdministrationAddOption
{
    public const NAME = 'siteAdministrationAddOption';

    /**
     * @var SiteAdministrationPluginOption[]
     */
    private $plugin_options = [];

    public function addPluginOption(SiteAdministrationPluginOption $plugin_option): void
    {
        $this->plugin_options[] = $plugin_option;
    }

    /**
     * @return SiteAdministrationPluginOption[]
     */
    public function getPluginOptions(): array
    {
        usort($this->plugin_options, static function (SiteAdministrationPluginOption $plugin_a, SiteAdministrationPluginOption $plugin_b): int {
            return strnatcasecmp($plugin_a->label, $plugin_b->label);
        });

        return $this->plugin_options;
    }
}
