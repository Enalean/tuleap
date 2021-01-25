/*
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
 */

import Gettext from "node-gettext";

export interface GettextProvider {
    gettext(msgid: string): string;
}

// See https://github.com/smhg/gettext-parser for the file format reference
interface Translation {
    readonly msgid: string;
    readonly msgstr: string;
}

interface TranslatedStrings {
    readonly [key: string]: Translation;
}

export interface Contexts {
    readonly [key: string]: TranslatedStrings;
}

export interface GettextParserPoFile {
    readonly translations: Contexts;
}

const DEFAULT_LANGUAGE = "en_US";

export function initGettextSync(
    domain: string,
    translations: GettextParserPoFile,
    locale = DEFAULT_LANGUAGE
): GettextProvider {
    const gettext_provider = new Gettext();
    if (locale !== DEFAULT_LANGUAGE) {
        gettext_provider.addTranslations(locale, domain, translations);
    }

    gettext_provider.setLocale(locale);
    gettext_provider.setTextDomain(domain);

    return gettext_provider;
}
