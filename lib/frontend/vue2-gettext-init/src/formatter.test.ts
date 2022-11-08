/*
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

import { describe, expect, it } from "vitest";
import { sanitizePoData } from "./formatter";

describe(`formatter`, () => {
    describe(`sanitizePoData()`, () => {
        it(`will rearrange the translations so that there is a single string for singular translations`, () => {
            const result = sanitizePoData([
                {
                    msgid: "radiological",
                    msgid_plural: null,
                    msgstr: ["radiologique"],
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                },
                {
                    msgid: "oxyrhynchus",
                    msgid_plural: null,
                    msgstr: ["oxyrhynchus"],
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                },
            ]);
            expect(result).toStrictEqual({
                radiological: "radiologique",
                oxyrhynchus: "oxyrhynchus",
            });
        });

        it(`will rearrange the translations so that there is an array for plural translations`, () => {
            const result = sanitizePoData([
                {
                    msgid: "1 warning",
                    msgid_plural: "%{ nb_warnings } warnings",
                    msgstr: ["1 avertissement", "%{ nb_warnings } avertissements"],
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                },
            ]);
            expect(result).toStrictEqual({
                "1 warning": ["1 avertissement", "%{ nb_warnings } avertissements"],
            });
        });

        it(`will preserve message context when not empty`, () => {
            const result = sanitizePoData([
                {
                    msgid: "Delete",
                    msgid_plural: null,
                    msgstr: ["Supprimer"],
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                },
                {
                    msgid: "Delete",
                    msgid_plural: null,
                    msgstr: ["Suppr"],
                    msgctxt: "keyboard key",
                    flags: {},
                    obsolete: false,
                },
            ]);
            expect(result).toStrictEqual({
                Delete: {
                    "": "Supprimer",
                    "keyboard key": "Suppr",
                },
            });
        });

        it(`will filter out fuzzy translations`, () => {
            const result = sanitizePoData([
                {
                    msgid: "fuzzy",
                    msgid_plural: null,
                    msgstr: ["fuzzy"],
                    msgctxt: null,
                    flags: { fuzzy: true },
                    obsolete: false,
                },
            ]);
            expect(result).toStrictEqual({});
        });

        it(`will filter out obsolete translations`, () => {
            const result = sanitizePoData([
                {
                    msgid: "obsolete",
                    msgid_plural: null,
                    msgstr: ["obsolete"],
                    msgctxt: null,
                    flags: {},
                    obsolete: true,
                },
            ]);
            expect(result).toStrictEqual({});
        });

        it(`will filter out untranslated strings`, () => {
            const result = sanitizePoData([
                {
                    msgid: "untranslated",
                    msgid_plural: null,
                    msgstr: [],
                    msgctxt: null,
                    flags: {},
                    obsolete: false,
                },
            ]);
            expect(result).toStrictEqual({});
        });
    });
});
