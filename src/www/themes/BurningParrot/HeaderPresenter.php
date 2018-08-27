<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use Feedback;
use PFUser;
use ThemeVariantColor;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenter;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\Theme\BurningParrot\Navbar\Presenter as NavbarPresenter;

class HeaderPresenter
{
    /** @var string */
    public $title;

    /** @var string */
    public $imgroot;

    /** @var Tuleap\Theme\BurningParrot\Navbar\Presenter */
    public $navbar_presenter;

    /** @var array */
    public $stylesheets;

    /** @var string */
    public $color_name;

    /** @var string */
    public $color_code;

    /** @var array */
    public $feedbacks;

    /** @var boolean */
    public $has_feedbacks;

    /** @var string */
    public $body_classes;

    /** @var string */
    public $main_classes;

    /** @var array */
    public $unicode_icons;

    /** @var SidebarPresenter */
    public $sidebar;

    /** @var CurrentProjectNavbarInfoPresenter */
    public $current_project_navbar_info_presenter;

    /** @var string[] HTML */
    public $toolbar;

    /** @var bool */
    public $has_toolbar;

    /** @var bool */
    public $has_motd;

    /** @var string */
    public $motd;

    /** @var array */
    public $breadcrumbs;

    /** @var bool */
    public $has_breadcrumbs;

    /** @var string */
    public $user_locale;

    /** @var int */
    public $user_id;

    public function __construct(
        PFUser $user,
        $title,
        $imgroot,
        NavbarPresenter $navbar_presenter,
        ThemeVariantColor $color,
        array $stylesheets,
        array $feedback_logs,
        $body_classes,
        $main_classes,
        $sidebar,
        $current_project_navbar_info_presenter,
        $unicode_icons,
        array $toolbar,
        array $breadcrumbs,
        $motd
    ) {
        $this->user_locale                           = $user->getLocale();
        $this->user_id                               = $user->getId();
        $this->title                                 = html_entity_decode($title);
        $this->imgroot                               = $imgroot;
        $this->navbar_presenter                      = $navbar_presenter;
        $this->stylesheets                           = $stylesheets;
        $this->color_name                            = $color->getName();
        $this->color_code                            = $color->getHexaCode();
        $this->body_classes                          = $body_classes;
        $this->main_classes                          = $main_classes;
        $this->sidebar                               = $sidebar;
        $this->current_project_navbar_info_presenter = $current_project_navbar_info_presenter;
        $this->unicode_icons                         = $unicode_icons;
        $this->toolbar                               = $toolbar;
        $this->motd                                  = $motd;
        $this->has_motd                              = ! empty($motd);
        $this->breadcrumbs                           = $breadcrumbs;

        $this->buildFeedbacks($feedback_logs);

        $this->has_toolbar              = count($toolbar) > 0;
        $this->has_feedbacks            = count($this->feedbacks) > 0;
        $this->has_breadcrumbs          = count($this->breadcrumbs) > 0;
    }

    private function buildFeedbacks($feedback_logs)
    {
        $this->feedbacks = array();
        $old_level = null;
        $purifier  = Codendi_HTMLPurifier::instance();
        $index     = -1;
        foreach ($feedback_logs as $feedback) {
            if ($old_level !== $feedback['level']) {
                ++$index;
                $this->feedbacks[$index] = array(
                    'level'             => $this->convertFeedbackLevel($feedback['level']),
                    'purified_messages' => array()
                );
                $old_level = $feedback['level'];
            }
            $this->feedbacks[$index]['purified_messages'][] = $purifier->purify($feedback['msg'], $feedback['purify']);
        }
    }

    private function convertFeedbackLevel($level)
    {
        switch ($level) {
            case Feedback::ERROR:
                return 'danger';
                break;
            case Feedback::DEBUG:
                return 'warning';
                break;
            default:
                return $level;
        }
    }
}
