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

let gettext_provider;

export async function initGettext(options) {
    if (typeof gettext_provider !== "undefined") {
        return gettext_provider;
    }

    gettext_provider = new Gettext();
    if (options.language === "fr_FR") {
        try {
            const french_translations = await import(/* webpackChunkName: "rich-text-editor-fr" */ "./po/fr.po");
            gettext_provider.addTranslations(
                options.language,
                "rich-text-editor",
                french_translations
            );
        } catch (exception) {
            // will be en_US if translations cannot be loaded
        }
    }

    gettext_provider.setLocale(options.language);
    gettext_provider.setTextDomain("rich-text-editor");

    return gettext_provider;
}

export function getGettextProvider() {
    if (typeof gettext_provider === "undefined") {
        throw new Error(`You need to initialize the provider first ! Call "initGettext"`);
    }
    return gettext_provider;
}
