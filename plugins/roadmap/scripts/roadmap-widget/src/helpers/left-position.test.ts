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

describe("getLeftForDate", () => {
    it("Gives a left position according to the time period", () => {
        expect(
            getLeftForDate(
                new Date(2020, 3, 15),
                new TimePeriodMonth(
                    new Date(2020, 1, 1),
                    new Date(2020, 3, 1),
                    new Date(2020, 4, 1),
                    "en_US"
                )
            )
        ).toBe(247);
        expect(
            getLeftForDate(
                new Date(2020, 3, 15),
                new TimePeriodQuarter(
                    new Date(2020, 1, 1),
                    new Date(2020, 3, 1),
                    new Date(2020, 4, 1),
                    createVueGettextProviderPassthrough()
                )
            )
        ).toBe(115);
    });
});
