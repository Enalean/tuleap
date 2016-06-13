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
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;

class HeaderPresenterBuilder
{
    /** @var Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder */
    private $navbar_presenter_builder;

    /** @var PFUser */
    private $current_user;

    /** @var string */
    private $imgroot;

    /** @var string */
    private $title;

    public function build(
        NavbarPresenterBuilder $navbar_presenter_builder,
        PFUser $current_user,
        $imgroot,
        $title
    ) {
        $this->navbar_presenter_builder = $navbar_presenter_builder;
        $this->current_user             = $current_user;
        $this->imgroot                  = $imgroot;
        $this->title                    = $title;

        return new HeaderPresenter(
            $this->getPageTitle(),
            $this->imgroot,
            $this->navbar_presenter_builder->build($this->current_user),
            $this->getStylesheets()
        );
    }

    private function getPageTitle()
    {
        $page_title = $GLOBALS['sys_name'];

        if (! empty($this->title)) {
            $page_title = $this->title .' - '. $page_title;
        }

        return $page_title;
    }

    private function getStylesheets()
    {
        $color = $this->getMainColor();

        return array(
            '/themes/common/css/font-awesome.css',
            '/themes/common/tlp/dist/tlp-'. $color .'.min.css',
            '/themes/BurningParrot/css/burning-parrot-'. $color .'.css'
        );
    }

    private function getMainColor()
    {
        $theme_variant = new ThemeVariant();
        $color = 'blue';
        switch ($theme_variant->getVariantForUser($this->current_user)) {
            case 'FlamingParrot_Orange':
            case 'FlamingParrot_DarkOrange':
                $color = 'orange';
                break;
            case 'FlamingParrot_Green':
            case 'FlamingParrot_DarkGreen':
                $color = 'green';
                break;
            case 'FlamingParrot_BlueGrey':
            case 'FlamingParrot_DarkBlueGrey':
                $color = 'grey';
                break;
            case 'FlamingParrot_Purple':
            case 'FlamingParrot_DarkPurple':
                $color = 'purple';
                break;
            case 'FlamingParrot_Red':
            case 'FlamingParrot_DarkRed':
                $color = 'red';
                break;
            case 'FlamingParrot_Blue':
            case 'FlamingParrot_DarkBlue':
            default:
                $color = 'blue';
        }

        return $color;
    }
}
