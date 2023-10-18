<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use PFUser;

final class EditionPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItChecksTheCommonMarkFormatByDefault(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, false],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'huhu'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'huhu'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_text_default_format_commonmark);
    }

    public function testItChecksTheCommonMarkFormatIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, PFUser::PREFERENCE_EDITION_COMMONMARK],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'hoho'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'hoho'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_text_default_format_commonmark);
        self::assertFalse($edition_presenter->user_text_default_format_html);
    }

    public function testItChecksTheHTMLFormatIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, PFUser::PREFERENCE_EDITION_HTML],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'haha'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'haha'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_text_default_format_commonmark);
        self::assertTrue($edition_presenter->user_text_default_format_html);
    }

    public function testItChecksTheCommaSeparatorByDefault(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'any_pref'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, false],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'any_pref'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheCommaSeparatorIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'pref_any'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, PFUser::PREFERENCE_CSV_COMMA],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'pref_any'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheSemicolonSeparatorIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'anypref_'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, PFUser::PREFERENCE_CSV_SEMICOLON],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'anypref_'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_separator_comma);
        self::assertTrue($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheTabSeparatorIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'petit_blagueur'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, PFUser::PREFERENCE_CSV_TAB],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, 'petit_blagueur'],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertTrue($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheMonthDayYearDateFormatByDefault(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'poti_blagueur'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'poti_blagueur'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, false],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertFalse($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }

    public function testItChecksTheMonthDayYearDateFormatIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'no_pref_idea'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'no_pref_idea'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, PFUser::PREFERENCE_CSV_MONTH_DAY_YEAR],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertFalse($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }

    public function testItChecksTheDayMonthYearDateFormatIfTheUserSelectedIt(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturnMap([
            [PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT, 'same'],
            [PFUser::PREFERENCE_NAME_CSV_SEPARATOR, 'same'],
            [PFUser::PREFERENCE_NAME_CSV_DATEFORMAT, PFUser::PREFERENCE_CSV_DAY_MONTH_YEAR],
        ]);

        $edition_presenter = new EditionPresenter(
            $this->createMock(\CSRFSynchronizerToken::class),
            $this->createMock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertTrue($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }
}
