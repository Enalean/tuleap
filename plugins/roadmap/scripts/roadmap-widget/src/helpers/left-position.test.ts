/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { getLeftForDate } from "./left-postion";
import { TimePeriodMonth } from "./time-period-month";
import { TimePeriodQuarter } from "./time-period-quarter";
import { createVueGettextProviderPassthrough } from "./vue-gettext-provider-for-test";
import { DateTime, Settings } from "luxon";

Settings.defaultZone = "UTC";

describe("getLeftForDate", () => {
    it("Gives a left position according to the time period", () => {
        expect(
            getLeftForDate(
                DateTime.fromJSDate(new Date("2020-04-14T22:00:00.000Z")),
                new TimePeriodMonth(
                    DateTime.fromJSDate(new Date("2020-01-31T23:00:00.000Z")),
                    DateTime.fromJSDate(new Date("2020-04-30T22:00:00.000Z")),
                    "en-US",
                ),
            ),
        ).toBe(346);
        expect(
            getLeftForDate(
                DateTime.fromJSDate(new Date("2020-04-14T22:00:00.000Z")),
                new TimePeriodQuarter(
                    DateTime.fromJSDate(new Date("2020-01-31T23:00:00.000Z")),
                    DateTime.fromJSDate(new Date("2020-04-30T22:00:00.000Z")),
                    createVueGettextProviderPassthrough(),
                ),
            ),
        ).toBe(115);
    });

    it("Gives a left position based on real user data", () => {
        expect(
            getLeftForDate(
                DateTime.fromJSDate(new Date("2021-04-01T00:00:00.000Z")),
                new TimePeriodMonth(
                    DateTime.fromJSDate(new Date("2021-03-31T14:36:12.580Z")),
                    DateTime.fromJSDate(new Date("2021-10-30T22:00:00.000Z")),
                    "en-US",
                ),
            ),
        ).toBe(100);
    });
});
