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

/* From https://github.com/Polyconseil/easygettext/ */

export type GettextTranslation = {
    readonly msgid: string;
    readonly msgid_plural: string | null;
    readonly msgstr: string[];
    readonly msgctxt: string | null;
    readonly flags: { readonly fuzzy?: true };
    readonly obsolete: boolean;
};

export type VueGettextTranslationsFormat = {
    readonly [msgid: string]: SingularOrPlural | ContextualizedTranslation;
};

type SingularOrPlural = string | string[];

type ContextualizedTranslation = {
    readonly [context: string]: SingularOrPlural;
};

type InternalMutableMap = {
    [msgid: string]: { [context: string]: SingularOrPlural };
};

type InternalMutableMapWithoutEmptyContext = {
    [msgid: string]: { [context: string]: SingularOrPlural } | SingularOrPlural;
};

/**
 * sanitizePoData
 *
 * Returns a sanitized po data dictionary where:
 * - no fuzzy or obsolete strings are returned
 * - no empty translations are returned
 *
 * @argument poItems: Object, items from the PO catalog
 *
 * @returns jsonData: Object, sanitized PO data
 *
 * {
 *   "Hello World": "Bonjour monde",
 *   "Thank you": {
 *     "à ma mère": "Merci, m'man",
 *     "à mon patron": "Je vous remercie",
 *   }
 * }
 */
export function sanitizePoData(
    poItems: readonly GettextTranslation[],
): VueGettextTranslationsFormat {
    const messages: InternalMutableMap = {};

    for (const item of poItems) {
        const ctx = item.msgctxt || "";
        if (item.msgstr[0] && item.msgstr[0].length > 0 && !item.flags.fuzzy && !item.obsolete) {
            if (!messages[item.msgid]) {
                messages[item.msgid] = {};
            }
            // Add an array for plural, a single string for singular.
            messages[item.msgid][ctx] = item.msgstr.length === 1 ? item.msgstr[0] : item.msgstr;
        }
    }

    const empty_context_stripped: InternalMutableMapWithoutEmptyContext = {};
    // Strip context from messages that have no context.
    for (const key in messages) {
        if (Object.keys(messages[key]).length === 1 && messages[key][""]) {
            empty_context_stripped[key] = messages[key][""];
        } else {
            empty_context_stripped[key] = messages[key];
        }
    }
    return empty_context_stripped;
}
