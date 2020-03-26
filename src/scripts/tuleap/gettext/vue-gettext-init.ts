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

import { VueConstructor } from "vue/types/vue";
import VueGettext from "vue-gettext";

export { getPOFileFromLocale } from "./gettext-init";

interface TranslatedStrings {
    readonly [key: string]: string;
}

interface GettextTranslationsMap {
    [locale: string]: TranslatedStrings;
}

export interface POFile {
    readonly messages: TranslatedStrings;
}

export async function initVueGettext(
    vue_instance: VueConstructor,
    load_translations_callback: (locale: string) => Promise<POFile>
): Promise<void> {
    const translations: GettextTranslationsMap = {};
    const locale = document.body.dataset.userLocale;
    if (locale) {
        try {
            translations[locale] = (await load_translations_callback(locale)).messages;
        } catch (exception) {
            // default to en_US
        }
    }
    vue_instance.use(VueGettext, {
        translations,
        silent: true,
    });
    if (locale) {
        vue_instance.config.language = locale;
    }
}
