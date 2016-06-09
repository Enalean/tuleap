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

namespace Tuleap\Theme\BurningParrot;

use ThemeVariant;
use PFUser;

class HeaderPresenter
{

    /** @var string */
    public $title;

    /** @var string */
    public $imgroot;

    /** @var string */
    public $color;

    public function __construct(
        PFUser $user,
        $title,
        $imgroot
    ) {
        $this->title   = html_entity_decode($title);
        $this->imgroot = $imgroot;

        $theme_variant = new ThemeVariant();
        $this->color = 'blue';
        switch ($theme_variant->getVariantForUser($user)) {
            case 'FlamingParrot_Orange':
            case 'FlamingParrot_DarkOrange':
                $this->color = 'orange';
                break;
            case 'FlamingParrot_Green':
            case 'FlamingParrot_DarkGreen':
                $this->color = 'green';
                break;
            case 'FlamingParrot_BlueGrey':
            case 'FlamingParrot_DarkBlueGrey':
                $this->color = 'grey';
                break;
            case 'FlamingParrot_Purple':
            case 'FlamingParrot_DarkPurple':
                $this->color = 'purple';
                break;
            case 'FlamingParrot_Red':
            case 'FlamingParrot_DarkRed':
                $this->color = 'red';
                break;
            case 'FlamingParrot_Blue':
            case 'FlamingParrot_DarkBlue':
            default:
                $this->color = 'blue';
        }
    }
}
