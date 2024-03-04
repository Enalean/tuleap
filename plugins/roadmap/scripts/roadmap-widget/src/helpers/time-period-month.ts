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

import type { TimePeriod } from "../type";
import { getBeginningOfPeriod, getEndOfPeriod } from "./begin-end-of-period";
import type { DateTime } from "luxon";

export class TimePeriodMonth implements TimePeriod {
    private readonly months: DateTime[];

    constructor(
        readonly from: DateTime,
        readonly to: DateTime,
        private readonly locale_bcp47: string,
    ) {
        this.months = getMonths(from, to);
    }

    static getDummyTimePeriod(now: DateTime): TimePeriod {
        return new TimePeriodMonth(now, now, "en-US");
    }

    get units(): DateTime[] {
        return this.months;
    }

    formatShort(unit: DateTime): string {
        return unit.setLocale(this.locale_bcp47).toLocaleString({
            month: "short",
        });
    }

    formatLong(unit: DateTime): string {
        return unit.setLocale(this.locale_bcp47).toLocaleString({
            month: "long",
            year: "numeric",
        });
    }

    additionalUnits(nb: number): DateTime[] {
        return getAdditionalMonths(this.months[this.months.length - 1], nb);
    }

    getEvenOddClass(): string {
        return "";
    }
}

function getMonths(start: DateTime, end: DateTime): DateTime[] {
    const beginning_of_period = getBeginningOfPeriod(start, end);
    const end_of_period = getEndOfPeriod(start, end);

    const base_month = beginning_of_period.startOf("month");

    const months = [base_month];
    let i = 1;
    while (months[months.length - 1] < end_of_period) {
        months.push(getBeginningOfNextNthMonth(base_month, i++));
    }

    return months;
}

function getAdditionalMonths(base_month: DateTime, nb_missing_months: number): DateTime[] {
    const additional_months: DateTime[] = [];

    if (nb_missing_months <= 0) {
        return additional_months;
    }

    for (let i = 0; i < nb_missing_months; i++) {
        additional_months.push(getBeginningOfNextNthMonth(base_month, i + 1));
    }

    return additional_months;
}

function getBeginningOfNextNthMonth(base_month: DateTime, nth: number): DateTime {
    return base_month.startOf("month").plus({ month: nth });
}
