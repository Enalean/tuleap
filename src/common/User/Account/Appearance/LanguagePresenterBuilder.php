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

use BaseLanguageFactory;

final class LanguagePresenterBuilder
{
    /**
     * @var BaseLanguageFactory
     */
    private $language_factory;

    public function __construct(BaseLanguageFactory $language_factory)
    {
        $this->language_factory = $language_factory;
    }

    /**
     * @return LanguagePresenter[]
     */
    public function getLanguagePresenterCollectionForUser(\PFUser $user): array
    {
        $languages   = [];
        $user_locale = $user->getLocale();
        foreach ($this->language_factory->getAvailableLanguages() as $locale => $label) {
            $is_checked  = $user_locale === $locale;
            $languages[] = new LanguagePresenter($locale, $label, $is_checked);
        }

        usort(
            $languages,
            static function (LanguagePresenter $a, LanguagePresenter $b): int {
                return strnatcasecmp($a->label, $b->label);
            }
        );

        return $languages;
    }
}
