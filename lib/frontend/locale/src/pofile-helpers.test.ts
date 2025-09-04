/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import { getPOFileFromLocale, getPOFileFromLocaleWithoutExtension } from "./pofile-helpers";
import { fr_FR_LOCALE } from "./constants";

describe(`pofile-helpers`, () => {
    describe(`getPOFileFromLocale`, () => {
        it("does not reject string looking like actual locale ID string", () => {
            expect(getPOFileFromLocale(fr_FR_LOCALE)).toBe("fr_FR.po");
        });

        it("rejects string that does not look like locale ID string", () => {
            expect(() => getPOFileFromLocale("not_a_locale")).toThrow();
        });
    });

    describe(`getPOFileFromLocaleWithoutExtension`, () => {
        it("does not reject string looking like actual locale ID string", () => {
            expect(getPOFileFromLocaleWithoutExtension(fr_FR_LOCALE)).toBe(fr_FR_LOCALE);
        });

        it("rejects string that does not look like locale ID string", () => {
            expect(() => getPOFileFromLocaleWithoutExtension("not_a_locale")).toThrow();
        });
    });
});
