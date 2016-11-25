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

use HTTPRequest;
use PFUser;
use Event;
use EventManager;
use ThemeVariant;
use ThemeVariantColor;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;
use URLRedirect;

class HeaderPresenterBuilder
{
    /** @var NavbarPresenterBuilder */
    private $navbar_presenter_builder;

    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    /** @var string */
    private $imgroot;

    /** @var string */
    private $title;

    /** @var array */
    private $main_classes;

    /** @var SidebarPresenter */
    private $sidebar;

    public function build(
        NavbarPresenterBuilder $navbar_presenter_builder,
        HTTPRequest $request,
        PFUser $current_user,
        $imgroot,
        $title,
        $feedback_logs,
        $main_classes,
        $sidebar,
        URLRedirect $url_redirect
    ) {
        $this->navbar_presenter_builder = $navbar_presenter_builder;
        $this->request                  = $request;
        $this->current_user             = $current_user;
        $this->imgroot                  = $imgroot;
        $this->title                    = $title;
        $this->main_classes             = $main_classes;
        $this->sidebar                  = $sidebar;

        $color = $this->getMainColor();

        return new HeaderPresenter(
            $this->getPageTitle(),
            $this->imgroot,
            $this->navbar_presenter_builder->build(
                $this->request,
                $this->current_user,
                $this->getExtraTabs(),
                $url_redirect
            ),
            $color,
            $this->getStylesheets($color),
            $feedback_logs,
            $this->getMainClassesAsString(),
            $this->sidebar
        );
    }

    private function getExtraTabs()
    {
        $additional_tabs = array();

        include $GLOBALS['Language']->getContent('layout/extra_tabs', null, null, '.php');

        if ($GLOBALS['sys_use_snippet'] != 0) {
            $selected = (boolean) strstr(getStringFromServer('REQUEST_URI'),'/snippet/');

            array_unshift($additional_tabs, array(
                    'link'      => '/snippet/',
                    'title'     => $GLOBALS['Language']->getText('include_menu','code_snippets'),
                    'selected'  => $selected,
                )
            );
        }

        return $additional_tabs;
    }

    private function getPageTitle()
    {
        $page_title = $GLOBALS['sys_name'];

        if (! empty($this->title)) {
            $page_title = $this->title .' - '. $page_title;
        }

        return $page_title;
    }

    private function getStylesheets(ThemeVariantColor $color)
    {
        $stylesheets = array(
            '/themes/common/tlp/dist/tlp-'. $color->getName() .'.min.css',
            '/themes/BurningParrot/css/burning-parrot-'. $color->getName() .'.css'
        );

        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_STYLESHEETS,
            array(
                'variant' => $this->getMainColor(),
                'stylesheets' => &$stylesheets
            )
        );

        return $stylesheets;
    }

    private function getMainColor()
    {
        $theme_variant = new ThemeVariant();
        $color         = new ThemeVariantColor('blue', '#1593c4');

        switch ($theme_variant->getVariantForUser($this->current_user)) {
            case 'FlamingParrot_Orange':
            case 'FlamingParrot_DarkOrange':
                $color = new ThemeVariantColor('orange', '#f79514');
                break;
            case 'FlamingParrot_Green':
            case 'FlamingParrot_DarkGreen':
                $color = new ThemeVariantColor('green', '#67af45');
                break;
            case 'FlamingParrot_BlueGrey':
            case 'FlamingParrot_DarkBlueGrey':
                $color = new ThemeVariantColor('grey', '#5b6c79');
                break;
            case 'FlamingParrot_Purple':
            case 'FlamingParrot_DarkPurple':
                $color = new ThemeVariantColor('purple', '#79558a');
                break;
            case 'FlamingParrot_Red':
            case 'FlamingParrot_DarkRed':
                $color = new ThemeVariantColor('red', '#bd2626');
                break;
            case 'FlamingParrot_Blue':
            case 'FlamingParrot_DarkBlue':
            default:
                $color = new ThemeVariantColor('blue', '#1593c4');
        }

        return $color;
    }

    private function getMainClassesAsString()
    {
        return implode(' ', $this->main_classes);
    }
}
