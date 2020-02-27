<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use PFUser;

final class EditionPresenter
{
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var bool
     */
    public $user_text_default_format_html;
    /**
     * @var bool
     */
    public $user_text_default_format_text;
    /**
     * @var bool
     */
    public $user_csv_separator_comma;
    /**
     * @var bool
     */
    public $user_csv_separator_semicolon;
    /**
     * @var bool
     */
    public $user_csv_separator_tab;
    /**
     * @var bool
     */
    public $user_csv_dateformat_mmddyyyy;
    /**
     * @var bool
     */
    public $user_csv_dateformat_ddmmyyyy;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        AccountTabPresenterCollection $tabs,
        PFUser $user
    ) {
        $this->csrf_token = $csrf_token;
        $this->tabs       = $tabs;

        $text_default_format = $user->getPreference(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT);
        if ($text_default_format === false) {
            $text_default_format = PFUser::PREFERENCE_EDITION_TEXT;
        }
        $csv_separator = $user->getPreference(PFUser::PREFERENCE_NAME_CSV_SEPARATOR);
        if ($csv_separator === false) {
            $csv_separator = PFUser::DEFAULT_CSV_SEPARATOR;
        }
        $csv_dateformat = $user->getPreference(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT);
        if ($csv_dateformat === false) {
            $csv_dateformat = PFUser::DEFAULT_CSV_DATEFORMAT;
        }

        $this->user_text_default_format_html = $text_default_format === PFUser::PREFERENCE_EDITION_HTML;
        $this->user_text_default_format_text = $text_default_format === PFUser::PREFERENCE_EDITION_TEXT;

        $this->user_csv_separator_comma     = $csv_separator === PFUser::PREFERENCE_CSV_COMMA;
        $this->user_csv_separator_semicolon = $csv_separator === PFUser::PREFERENCE_CSV_SEMICOLON;
        $this->user_csv_separator_tab       = $csv_separator === PFUser::PREFERENCE_CSV_TAB;

        $this->user_csv_dateformat_mmddyyyy = $csv_dateformat === PFUser::PREFERENCE_CSV_MONTH_DAY_YEAR;
        $this->user_csv_dateformat_ddmmyyyy = $csv_dateformat === PFUser::PREFERENCE_CSV_DAY_MONTH_YEAR;
    }
}
