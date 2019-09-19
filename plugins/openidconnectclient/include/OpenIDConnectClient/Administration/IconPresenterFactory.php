<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\Provider;

class IconPresenterFactory
{

    private $available_icons = array(
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
        'heart'
    );

    public function getIconsPresenters()
    {
        $icons_presenters = array();

        foreach ($this->available_icons as $icon) {
            $icons_presenters[] = new IconPresenter($icon, false);
        }

        return $icons_presenters;
    }

    public function getIconsPresentersForProvider(Provider $provider)
    {
        $icons_presenters = array();

        foreach ($this->available_icons as $icon) {
            $is_icon_selected = false;

            if ($provider->getIcon() === $icon) {
                $is_icon_selected = true;
            }

            $icons_presenters[] = new IconPresenter($icon, $is_icon_selected);
        }

        return $icons_presenters;
    }
}
