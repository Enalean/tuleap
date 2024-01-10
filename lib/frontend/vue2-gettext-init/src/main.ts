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

import type { VueConstructor } from "vue/types/vue";
import VueGettext from "vue-gettext";
import type { GettextTranslation, VueGettextTranslationsFormat } from "./formatter";
import { sanitizePoData } from "./formatter";

export { getPOFileFromLocale, getPOFileFromLocaleWithoutExtension } from "@tuleap/gettext";
export type { GetText } from "@tuleap/gettext";

interface GettextTranslationsMap {
    [locale: string]: VueGettextTranslationsFormat;
}

interface VueGettextPOFile {
    readonly messages: VueGettextTranslationsFormat;
}

const loadTranslations = (
    locale: string | undefined,
    load_translations_callback: (locale: string) => PromiseLike<VueGettextPOFile>,
): PromiseLike<GettextTranslationsMap> => {
    if (!locale) {
        return Promise.resolve({});
    }
    return load_translations_callback(locale).then(
        (po_file) => {
            const translations: GettextTranslationsMap = {};
            translations[locale] = po_file.messages;
            return translations;
        },
        () => {
            // default to en_US
            return {};
        },
    );
};

type POGettextTranslation = {
    readonly msgid: string;
    readonly msgstr: string[];
};

export type POGettextPluginPOFile = {
    readonly translations: {
        readonly "": {
            readonly [msgid: string]: POGettextTranslation;
        };
    };
};

export async function initVueGettext(
    vue_instance: VueConstructor,
    load_translations_callback: (locale: string) => PromiseLike<POGettextPluginPOFile>,
): Promise<void> {
    const locale = document.body.dataset.userLocale;
    const translations = await loadTranslations(locale, (locale) =>
        load_translations_callback(locale).then((po_file) => {
            const mapped = Object.values(po_file.translations[""]).map(
                (translation: POGettextTranslation): GettextTranslation => ({
                    ...translation,
                    msgid_plural: null,
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                }),
            );
            return { messages: sanitizePoData(mapped) };
        }),
    );
    vue_instance.use(VueGettext, { translations, silent: true });
    if (locale) {
        vue_instance.config.language = locale;
    }
}
