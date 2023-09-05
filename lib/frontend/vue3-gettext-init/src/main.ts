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

import type { createGettext } from "vue3-gettext";

export { getPOFileFromLocale, getPOFileFromLocaleWithoutExtension } from "@tuleap/gettext";

type VueGettext = ReturnType<typeof createGettext>;

interface Vue3GettextTranslationData {
    [message_id: string]: string[];
}

interface Vue3GettextTranslationMap {
    [language: string]: Vue3GettextTranslationData;
}

const loadTranslations = (
    locale: string | undefined,
    load_translations_callback: (locale: string) => PromiseLike<POGettextPluginPOFile>,
): PromiseLike<Vue3GettextTranslationMap> => {
    if (!locale) {
        return Promise.resolve({});
    }
    return load_translations_callback(locale).then(
        (po_file) => {
            const translations: Vue3GettextTranslationMap = {};
            translations[locale] = transformTranslationToVue3GettextFormat(po_file);
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
    create_gettext: typeof createGettext,
    load_translations_callback: (locale: string) => PromiseLike<POGettextPluginPOFile>,
): Promise<VueGettext> {
    const locale = document.body.dataset.userLocale;
    return create_gettext({
        defaultLanguage: locale ?? "",
        translations: await loadTranslations(locale, load_translations_callback),
        silent: true,
    });
}

function transformTranslationToVue3GettextFormat(
    po_file: POGettextPluginPOFile,
): Vue3GettextTranslationData {
    const vue3_gettext_data: Vue3GettextTranslationData = {};
    for (const [, value] of Object.entries(po_file.translations[""])) {
        if (value.msgstr.every((msgstr) => msgstr.length !== 0)) {
            vue3_gettext_data[value.msgid] = value.msgstr;
        }
    }
    return vue3_gettext_data;
}
