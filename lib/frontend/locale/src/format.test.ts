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
import type { LocaleString } from "./constants";
import { en_US_LOCALE, fr_FR_LOCALE, ko_KR_LOCALE, pt_BR_LOCALE } from "./constants";
import { toBCP47 } from "./format";

describe(`format`, () => {
    function* generateLocaleStrings(): Generator<[LocaleString, string]> {
        yield [en_US_LOCALE, "en-US"];
        yield [fr_FR_LOCALE, "fr-FR"];
        yield [pt_BR_LOCALE, "pt-BR"];
        yield [ko_KR_LOCALE, "ko-KR"];
    }

    it.each([...generateLocaleStrings()])(
        `converts from Tuleap locale string format to BCP47 locale string`,
        (locale_string, expected) => {
            expect(toBCP47(locale_string)).toBe(expected);
        },
    );
});
