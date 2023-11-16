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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account\Appearance;

use CSRFSynchronizerToken;
use Tuleap\Date\RelativeDatesDisplayPreferencesSelectboxPresenter;
use Tuleap\Date\RelativeDatesDisplayPreferencesSelectboxPresenterBuilder;
use Tuleap\User\Account\AccountTabPresenterCollection;

final class AppearancePresenter
{
    /**
     * @var CSRFSynchronizerToken
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var AccountTabPresenterCollection
     * @psalm-readonly
     */
    public $tabs;
    /**
     * @var LanguagePresenter[]
     * @psalm-readonly
     */
    public $languages;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $json_encoded_colors;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_condensed;
    /**
     * @var string
     * @psalm-readonly
     */
    public $current_color;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_accessibility_enabled;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_realname_login;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_login_realname;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_login;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_realname;
    /**
     * @var int
     * @psalm-readonly
     */
    public $username_display_value_realname_login;
    /**
     * @var int
     * @psalm-readonly
     */
    public $username_display_value_login_realname;
    /**
     * @var int
     * @psalm-readonly
     */
    public $username_display_value_login;
    /**
     * @var int
     * @psalm-readonly
     */
    public $username_display_value_realname;

    /**
     * @var RelativeDatesDisplayPreferencesSelectboxPresenter
     * @psalm-readonly
     */
    public $relative_dates_display_preference_sb_presenter;

    /**
     * @var string
     * @psalm-readonly
     */
    public $date_for_preview = '';

    /**
     * @param LanguagePresenter[]   $languages
     * @param ThemeColorPresenter[] $colors
     */
    public function __construct(
        CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs,
        array $languages,
        array $colors,
        bool $is_condensed,
        bool $is_accessibility_enabled,
        bool $is_realname_login,
        bool $is_login_realname,
        bool $is_login,
        bool $is_realname,
        string $relative_dates_display,
    ) {
        $this->csrf_token               = $csrf_token;
        $this->tabs                     = $tabs;
        $this->languages                = $languages;
        $this->json_encoded_colors      = json_encode($colors);
        $this->is_condensed             = $is_condensed;
        $this->is_accessibility_enabled = $is_accessibility_enabled;
        $this->is_realname_login        = $is_realname_login;
        $this->is_login_realname        = $is_login_realname;
        $this->is_login                 = $is_login;
        $this->is_realname              = $is_realname;

        $this->username_display_value_realname_login = \UserHelper::PREFERENCES_NAME_AND_LOGIN;
        $this->username_display_value_login_realname = \UserHelper::PREFERENCES_LOGIN_AND_NAME;
        $this->username_display_value_login          = \UserHelper::PREFERENCES_LOGIN;
        $this->username_display_value_realname       = \UserHelper::PREFERENCES_REAL_NAME;

        $presenter_builder                                    = new RelativeDatesDisplayPreferencesSelectboxPresenterBuilder();
        $this->relative_dates_display_preference_sb_presenter = $presenter_builder->build($relative_dates_display);

        $five_days_ago_timestamp = (new \DateTimeImmutable())->sub(
            new \DateInterval('P5D')
        )->getTimestamp();
        $date_five_days_ago      = date($GLOBALS['Language']->getText('system', 'datefmt_short'), $five_days_ago_timestamp);

        if ($date_five_days_ago) {
            $this->date_for_preview = $date_five_days_ago;
        }

        $this->current_color = '';
        foreach ($colors as $color) {
            if ($color->selected) {
                $this->current_color = $color->id;
            }
        }
    }
}
