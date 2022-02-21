/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { createGettext } from "vue3-gettext";

type VueGettext = ReturnType<typeof createGettext>;

interface TranslatedStrings {
    readonly [key: string]: string;
}

interface GettextTranslationsMap {
    [locale: string]: TranslatedStrings;
}

export interface POFile {
    readonly messages: TranslatedStrings;
}

export {
    getPOFileFromLocale,
    getPOFileFromLocaleWithoutExtension,
} from "../../../../scripts/tuleap/gettext/gettext-init";

export async function initVueGettext(
    load_translations_callback: (locale: string) => Promise<POFile>
): Promise<VueGettext> {
    const translations: GettextTranslationsMap = {};
    const locale = document.body.dataset.userLocale;
    if (locale) {
        try {
            translations[locale] = (await load_translations_callback(locale)).messages;
        } catch (exception) {
            // default to en_US
        }
    }

    return createGettext({
        translations,
        silent: true,
    });
}
