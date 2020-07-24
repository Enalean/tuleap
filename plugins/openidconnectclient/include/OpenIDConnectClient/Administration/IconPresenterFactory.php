<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\Provider;

class IconPresenterFactory
{
    private const AVAILABLE_ICONS = [
        'github',
        'google-plus',
        'linkedin',
        'facebook',
        'windows',
        'globe',
        'circle',
        'circle-blank',
        'cloud',
        'asterisk',
        'certificate',
        'heart',
        'tlp-tuleap',
    ];

    /**
     * @return IconPresenter[]
     */
    public function getIconsPresenters(): array
    {
        $icons_presenters = [];

        foreach (self::AVAILABLE_ICONS as $icon) {
            $icons_presenters[] = new IconPresenter($icon, false);
        }

        return $icons_presenters;
    }

    /**
     * @return IconPresenter[]
     */
    public function getIconsPresentersForProvider(Provider $provider): array
    {
        $icons_presenters = [];

        foreach (self::AVAILABLE_ICONS as $icon) {
            $is_icon_selected = false;

            if ($provider->getIcon() === $icon) {
                $is_icon_selected = true;
            }

            $icons_presenters[] = new IconPresenter($icon, $is_icon_selected);
        }

        return $icons_presenters;
    }
}
