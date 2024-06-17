/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import type { DateFormatType } from "./IntlFormatter";
import { IntlFormatter } from "./IntlFormatter";
import type { LocaleString } from "@tuleap/core-constants";
import { en_US_LOCALE, fr_FR_LOCALE, ko_KR_LOCALE } from "@tuleap/core-constants";

type Configuration = [LocaleString, string, DateFormatType];

const new_york = "America/New_York";
const paris = "Europe/Paris";

const us_time_config: Configuration = [en_US_LOCALE, new_york, "date-with-time"];
const us_date_config: Configuration = [en_US_LOCALE, new_york, "date"];
const us_short_month_config: Configuration = [en_US_LOCALE, new_york, "short-month"];
const fr_time_config: Configuration = [fr_FR_LOCALE, paris, "date-with-time"];
const fr_date_config: Configuration = [fr_FR_LOCALE, paris, "date"];
const fr_short_month_config: Configuration = [fr_FR_LOCALE, paris, "short-month"];
const config_defaulting_to_iso: Configuration = [ko_KR_LOCALE, "Asia/Seoul", "date-with-time"];

describe(`IntlFormatter`, () => {
    function* generateInvalidStrings(): Generator<[string | null | undefined]> {
        yield [null];
        yield [undefined];
        yield [""];
    }

    it.each([...generateInvalidStrings()])(
        `when it is called with an invalid string, it will return an empty string`,
        (input) => {
            const formatter = IntlFormatter(...us_time_config);
            expect(formatter.format(input)).toBe("");
        },
    );

    it(`throws an error when called with an invalid date string`, () => {
        const formatter = IntlFormatter(...us_time_config);
        expect(() => formatter.format("JxUwj0n")).toThrow();
    });

    function* generateValidISO8601DateStrings(): Generator<
        [LocaleString, string, DateFormatType, string, string]
    > {
        const base_CEST = "2024-06-17T16:56:05+02:00";
        const base_CET = "2024-03-14T12:05:05+01:00";
        yield [...us_time_config, base_CEST, "2024-06-17 10:56"];
        yield [...us_time_config, base_CET, "2024-03-14 07:05"];
        yield [...us_time_config, "2024-06-18T04:27:27+02:00", "2024-06-17 22:27"];
        yield [...config_defaulting_to_iso, base_CEST, "2024-06-17 23:56"];
        yield [...fr_time_config, base_CEST, "17/06/2024 16:56"];
        yield [...fr_time_config, base_CET, "14/03/2024 12:05"];
        yield [...fr_time_config, "2024-06-17T22:21:08-12:00", "18/06/2024 12:21"];
        yield [...us_date_config, base_CEST, "2024-06-17"];
        yield [...us_date_config, "2016-01-01T03:03:49+06:00", "2015-12-31"];
        yield [...fr_date_config, base_CEST, "17/06/2024"];
        yield [...fr_date_config, "2025-12-31T22:15:16-04:00", "01/01/2026"];
        yield [...us_short_month_config, base_CEST, "Jun 17, 2024"];
        yield [...fr_short_month_config, base_CEST, "17 juin 2024"];
    }

    it.each([...generateValidISO8601DateStrings()])(
        `given a formatter configured for %s locale and %s timezone and %s date format,
        when it is called with a valid ISO8601 string %s
        then it will return the formatted string %s`,
        (locale, timezone, type, input, expected_output) => {
            const formatter = IntlFormatter(locale, timezone, type);
            expect(formatter.format(input)).toBe(expected_output);
        },
    );
});
