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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;

final class EditionPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItChecksTheCommonMarkFormatByDefault(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturnFalse();
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('huhu');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('huhu');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_text_default_format_commonmark);
    }

    public function testItChecksTheCommonMarkFormatIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn(PFUser::PREFERENCE_EDITION_COMMONMARK);
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('hoho');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('hoho');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_text_default_format_commonmark);
        self::assertFalse($edition_presenter->user_text_default_format_html);
    }

    public function testItChecksTheHTMLFormatIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn(PFUser::PREFERENCE_EDITION_HTML);
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('haha');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('haha');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_text_default_format_commonmark);
        self::assertTrue($edition_presenter->user_text_default_format_html);
    }

    public function testItChecksTheCommaSeparatorByDefault(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('any_pref');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturnFalse();
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('any_pref');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheCommaSeparatorIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('pref_any');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn(PFUser::PREFERENCE_CSV_COMMA);
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('pref_any');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheSemicolonSeparatorIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('anypref_');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn(PFUser::PREFERENCE_CSV_SEMICOLON);
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('anypref_');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_separator_comma);
        self::assertTrue($edition_presenter->user_csv_separator_semicolon);
        self::assertFalse($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheTabSeparatorIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('petit_blagueur');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn(PFUser::PREFERENCE_CSV_TAB);
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn('petit_blagueur');

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_separator_comma);
        self::assertFalse($edition_presenter->user_csv_separator_semicolon);
        self::assertTrue($edition_presenter->user_csv_separator_tab);
    }

    public function testItChecksTheMonthDayYearDateFormatByDefault(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('poti_blagueur');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('poti_blagueur');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturnFalse();

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertFalse($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }

    public function testItChecksTheMonthDayYearDateFormatIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('no_pref_idea');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('no_pref_idea');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn(PFUser::PREFERENCE_CSV_MONTH_DAY_YEAR);

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertTrue($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertFalse($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }

    public function testItChecksTheDayMonthYearDateFormatIfTheUserSelectedIt(): void
    {
        $user = Mockery::mock(PFUser::class);

        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_EDITION_DEFAULT_FORMAT)->andReturn('same');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_SEPARATOR)->andReturn('same');
        $user->shouldReceive('getPreference')->with(PFUser::PREFERENCE_NAME_CSV_DATEFORMAT)->andReturn(PFUser::PREFERENCE_CSV_DAY_MONTH_YEAR);

        $edition_presenter = new EditionPresenter(
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::mock(AccountTabPresenterCollection::class),
            $user
        );
        self::assertFalse($edition_presenter->user_csv_dateformat_mmddyyyy);
        self::assertTrue($edition_presenter->user_csv_dateformat_ddmmyyyy);
    }
}
