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
import { toBCP47 } from "./locale-for-intl";
import { getBeginningOfPeriod, getEndOfPeriod } from "./begin-end-of-period";

export class TimePeriodMonth implements TimePeriod {
    private readonly months: Date[];
    private readonly formatter_short: Intl.DateTimeFormat;
    private readonly formatter_long: Intl.DateTimeFormat;

    constructor(
        readonly from: Date | null,
        readonly to: Date | null,
        readonly now: Date,
        readonly locale: string
    ) {
        this.months = getMonths(from, to, now);
        this.formatter_short = new Intl.DateTimeFormat(toBCP47(locale), {
            month: "short",
        });
        this.formatter_long = new Intl.DateTimeFormat(toBCP47(locale), {
            month: "long",
            year: "numeric",
        });
    }

    static getDummyTimePeriod(now: Date): TimePeriod {
        return new TimePeriodMonth(now, now, now, "en_US");
    }

    get units(): Date[] {
        return this.months;
    }

    formatShort(unit: Date): string {
        return this.formatter_short.format(unit);
    }

    formatLong(unit: Date): string {
        return this.formatter_long.format(unit);
    }

    additionalUnits(nb: number): Date[] {
        return getAdditionalMonths(this.months[this.months.length - 1], nb);
    }

    getBeginningOfNextNthUnit(unit: Date, nth: number): Date {
        return getBeginningOfNextNthMonth(unit, nth);
    }
}

function getMonths(start: Date | null, end: Date | null, now: Date): Date[] {
    const beginning_of_period = getBeginningOfPeriod(start, end, now);
    const end_of_period = getEndOfPeriod(start, end, now);

    const months = [new Date(new Date(beginning_of_period).setDate(1))];
    let i = 1;
    while (months[months.length - 1] < end_of_period) {
        months.push(getBeginningOfNextNthMonth(beginning_of_period, i++));
    }

    return months;
}

function getAdditionalMonths(base_month: Date, nb_missing_months: number): Date[] {
    const additional_months: Date[] = [];

    if (nb_missing_months <= 0) {
        return additional_months;
    }

    for (let i = 0; i < nb_missing_months; i++) {
        additional_months.push(getBeginningOfNextNthMonth(base_month, i + 1));
    }

    return additional_months;
}

function getBeginningOfNextNthMonth(base_month: Date, nth: number): Date {
    const same_date_months_later = new Date(
        new Date(base_month).setMonth(base_month.getMonth() + nth)
    );

    return new Date(same_date_months_later.setDate(1));
}
