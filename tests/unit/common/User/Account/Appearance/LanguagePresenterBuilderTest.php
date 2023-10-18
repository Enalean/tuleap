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

namespace Tuleap\User\Account\Appearance;

use Tuleap\Language\LocaleSwitcher;

final class LanguagePresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetLanguagePresenterCollectionForUser()
    {
        $factory = $this->createMock(\BaseLanguageFactory::class);
        $factory
            ->expects(self::once())
            ->method('getAvailableLanguages')
            ->willReturn([
                'ja_JP' => '日本語',
                'en_US' => 'English',
                'fr_FR' => 'Français',
            ]);

        $user = $this->createMock(\PFUser::class);
        $user
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('fr_FR');

        $builder           = new LanguagePresenterBuilder($factory, new LocaleSwitcher());
        $beta_warning_text = "Work in progress, you might find untranslated strings.";
        self::assertEquals(
            [
                new LanguagePresenter('en_US', 'English', false, true, $beta_warning_text),
                new LanguagePresenter('fr_FR', 'Français', true, true, $beta_warning_text),
                new LanguagePresenter('ja_JP', '日本語', false, false, $beta_warning_text),
            ],
            $builder->getLanguagePresenterCollectionForUser($user)
        );
    }
}
