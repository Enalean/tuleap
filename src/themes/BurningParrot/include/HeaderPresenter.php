<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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
use Tuleap\HelpDropdown\HelpDropdownPresenter;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\OpenGraph\OpenGraphPresenter;
use Tuleap\Project\ProjectContextPresenter;
use Tuleap\Project\ProjectPrivacyPresenter;
use Tuleap\Theme\BurningParrot\Navbar\Presenter as NavbarPresenter;
use Tuleap\TimezoneRetriever;
use Tuleap\User\SwitchToPresenter;

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

    /** @var bool */
    public $has_feedbacks;

    /** @var string */
    public $body_classes;

    /** @var string */
    public $main_classes;

    /** @var SidebarPresenter */
    public $sidebar;

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
    /** @var string */
    public $user_timezone;
    /** @var string */
    public $date_time_format;
    /**
     * @var OpenGraphPresenter
     */
    public $open_graph;
    /**
     * @var bool
     */
    public $user_has_accessibility_mode;
    /**
     * @var HelpDropdownPresenter
     */
    public $help_dropdown;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_only_one_breadcrumb;
    /**
     * @var ProjectPrivacyPresenter|false
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var array
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_project_flags;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $json_encoded_project_flags;
    /**
     * @var int
     * @psalm-readonly
     */
    public $nb_project_flags;
    /**
     * @var string
     * @psalm-readonly
     */
    public $purified_banner;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $project_banner_is_visible;
    /**
     * @var false|int
     */
    public $project_id;
    /**
     * @var ProjectContextPresenter|null
     */
    public $project_context;
    /**
     * @var SwitchToPresenter|null
     */
    public $switch_to;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_legacy_logo_customized;

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
        array $toolbar,
        array $breadcrumbs,
        $motd,
        OpenGraphPresenter $open_graph,
        HelpDropdownPresenter $help_dropdown_presenter,
        ?ProjectContextPresenter $project_context,
        ?SwitchToPresenter $switch_to,
        bool $is_legacy_logo_customized
    ) {
        $this->date_time_format            = $GLOBALS['Language']->getText('system', 'datefmt');
        $this->user_timezone               = TimezoneRetriever::getUserTimezone($user);
        $this->user_locale                 = $user->getLocale();
        $this->user_id                     = $user->getId();
        $this->title                       = html_entity_decode($title);
        $this->imgroot                     = $imgroot;
        $this->navbar_presenter            = $navbar_presenter;
        $this->stylesheets                 = $stylesheets;
        $this->color_name                  = $color->getName();
        $this->color_code                  = $color->getHexaCode();
        $this->body_classes                = $body_classes;
        $this->main_classes                = $main_classes;
        $this->sidebar                     = $sidebar;
        $this->toolbar                     = $toolbar;
        $this->motd                        = $motd;
        $this->has_motd                    = ! empty($motd);
        $this->breadcrumbs                 = $breadcrumbs;
        $this->open_graph                  = $open_graph;
        $this->help_dropdown               = $help_dropdown_presenter;
        $this->user_has_accessibility_mode = (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE);
        $this->project_context             = $project_context;
        $this->switch_to                   = $switch_to;
        $this->is_legacy_logo_customized   = $is_legacy_logo_customized;

        $this->buildFeedbacks($feedback_logs);

        $this->has_toolbar              = count($toolbar) > 0;
        $this->has_feedbacks            = count($this->feedbacks) > 0;
        $this->has_breadcrumbs          = count($this->breadcrumbs) > 0;
        $this->has_only_one_breadcrumb  = count($this->breadcrumbs) === 1;
    }

    private function buildFeedbacks($feedback_logs)
    {
        $this->feedbacks = [];
        $old_level = null;
        $purifier  = Codendi_HTMLPurifier::instance();
        $index     = -1;
        foreach ($feedback_logs as $feedback) {
            if ($old_level !== $feedback['level']) {
                ++$index;
                $this->feedbacks[$index] = [
                    'level'             => $this->convertFeedbackLevel($feedback['level']),
                    'purified_messages' => []
                ];
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
