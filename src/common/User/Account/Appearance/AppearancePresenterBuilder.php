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
use PFUser;
use Tuleap\Date\DateHelper;
use Tuleap\Date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\User\Account\AccountTabPresenterCollection;
use UserHelper;

class AppearancePresenterBuilder
{
    /**
     * @var LanguagePresenterBuilder
     */
    private $language_presenter_builder;
    /**
     * @var ThemeColorPresenterBuilder
     */
    private $color_presenter_builder;

    public function __construct(
        LanguagePresenterBuilder $language_presenter_builder,
        ThemeColorPresenterBuilder $color_presenter_builder,
    ) {
        $this->language_presenter_builder = $language_presenter_builder;
        $this->color_presenter_builder    = $color_presenter_builder;
    }

    public function getAppareancePresenterForUser(
        CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs,
        \PFUser $user,
    ): AppearancePresenter {
        $is_condensed = $user->getPreference(PFUser::PREFERENCE_DISPLAY_DENSITY) === PFUser::DISPLAY_DENSITY_CONDENSED;

        $is_accessibility_enabled = (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE);

        $preference        = (int) $user->getPreference(PFUser::PREFERENCE_NAME_DISPLAY_USERS);
        $is_realname_login = $preference === UserHelper::PREFERENCES_NAME_AND_LOGIN;
        $is_login_realname = $preference === UserHelper::PREFERENCES_LOGIN_AND_NAME;
        $is_login          = $preference === UserHelper::PREFERENCES_LOGIN;
        $is_realname       = $preference === UserHelper::PREFERENCES_REAL_NAME;

        $display_relative_dates_preference = $user->getPreference(DateHelper::PREFERENCE_NAME);

        return new AppearancePresenter(
            $csrf_token,
            $tabs,
            $this->language_presenter_builder->getLanguagePresenterCollectionForUser($user),
            $this->color_presenter_builder->getColorPresenterCollection($user),
            $is_condensed,
            $is_accessibility_enabled,
            $is_realname_login,
            $is_login_realname,
            $is_login,
            $is_realname,
            $display_relative_dates_preference ?: DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue()
        );
    }
}
