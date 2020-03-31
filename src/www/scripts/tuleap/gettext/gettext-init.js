/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import Gettext from "node-gettext";

export function getPOFileFromLocale(locale) {
    if (!locale.match(/[a-z]{2,3}_[A-Z]{2,3}/)) {
        throw new Error(`${locale} does not not seem to be a locale string`);
    }
    return locale + ".po";
}

export async function initGettext(locale, domain, load_translations_callback) {
    const gettext_provider = new Gettext();
    if (locale !== "en_US") {
        try {
            gettext_provider.addTranslations(
                locale,
                domain,
                await load_translations_callback(locale)
            );
        } catch (exception) {
            // will be en_US if translations cannot be loaded
        }
    }

    gettext_provider.setLocale(locale);
    gettext_provider.setTextDomain(domain);

    return gettext_provider;
}
