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
use Tuleap\Language\LocaleSwitcher;

final class LanguagePresenterBuilder
{
    public function __construct(
        private BaseLanguageFactory $language_factory,
        private LocaleSwitcher $locale_switcher,
    ) {
    }

    /**
     * @return LanguagePresenter[]
     */
    public function getLanguagePresenterCollectionForUser(\PFUser $user): array
    {
        $available_languages = new \ArrayObject();

        $user_locale = $user->getLocale();
        foreach ($this->language_factory->getAvailableLanguages() as $locale => $label) {
            $this->locale_switcher->setLocaleForSpecificExecutionContext(
                $locale,
                function () use ($locale, $label, $user_locale, $available_languages) {
                    $is_official_language = in_array($locale, ['en_US', 'fr_FR'], true);

                    $is_checked = $user_locale === $locale;
                    $available_languages->append(
                        new LanguagePresenter(
                            $locale,
                            $label,
                            $is_checked,
                            $is_official_language,
                            _("Work in progress, you might find untranslated strings.")
                        )
                    );
                }
            );
        }

        $languages = $available_languages->getArrayCopy();

        usort(
            $languages,
            static function (LanguagePresenter $a, LanguagePresenter $b): int {
                return strnatcasecmp($a->label, $b->label);
            }
        );

        return $languages;
    }
}
