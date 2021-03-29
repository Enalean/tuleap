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
import type { VueGettextProvider } from "./vue-gettext-provider";

export class TimePeriodQuarter implements TimePeriod {
    private readonly quarters: Date[];
    private gettext_provider: VueGettextProvider;

    constructor(
        readonly from: Date | null,
        readonly to: Date | null,
        readonly now: Date,
        gettext_provider: VueGettextProvider
    ) {
        this.gettext_provider = gettext_provider;
        this.quarters = getQuarters(from, to, now);
    }

    get units(): Date[] {
        return this.quarters;
    }

    formatShort(unit: Date): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Q%{ quarter }"),
            {
                quarter: getQuarterNumber(unit),
            }
        );
    }

    formatLong(unit: Date): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Quarter %{ quarter } of %{ year }"),
            {
                quarter: getQuarterNumber(unit),
                year: unit.getFullYear(),
            }
        );
    }

    additionalUnits(nb: number): Date[] {
        return getAdditionalQuarters(this.quarters[this.quarters.length - 1], nb);
    }

    getBeginningOfNextNthUnit(unit: Date, nth: number): Date {
        return getBeginningOfNextNthQuarter(unit, nth);
    }
}

function getQuarterNumber(unit: Date): number {
    return Math.ceil((unit.getMonth() + 1) / 3);
}

function getQuarters(start: Date | null, end: Date | null, now: Date): Date[] {
    const beginning_of_period = getBeginningOfPeriod(start, end, now);
    const end_of_period = getEndOfPeriod(start, end, now);

    const starting_quarter = new Date(
        beginning_of_period.getFullYear(),
        (getQuarterNumber(beginning_of_period) - 1) * 3,
        1
    );
    const quarters = [starting_quarter];

    let i = 1;
    while (quarters[quarters.length - 1] < end_of_period) {
        quarters.push(getBeginningOfNextNthQuarter(starting_quarter, i++));
    }

    return quarters;
}

function getBeginningOfPeriod(start: Date | null, end: Date | null, now: Date): Date {
    if (!start) {
        if (!end) {
            return now;
        }

        return now < end ? now : end;
    }

    if (!end) {
        return now < start ? now : start;
    }

    if (start <= end) {
        return now < start ? now : start;
    }

    return now < end ? now : end;
}

function getEndOfPeriod(start: Date | null, end: Date | null, now: Date): Date {
    if (!start) {
        if (!end) {
            return now;
        }

        return end < now ? now : end;
    }

    if (!end) {
        return start < now ? now : start;
    }

    if (start <= end) {
        return end < now ? now : end;
    }

    return start < now ? now : start;
}

function getAdditionalQuarters(base_quarter: Date, nb_missing_quarters: number): Date[] {
    const additional_quarters: Date[] = [];

    if (nb_missing_quarters <= 0) {
        return additional_quarters;
    }

    for (let i = 0; i < nb_missing_quarters; i++) {
        additional_quarters.push(getBeginningOfNextNthQuarter(base_quarter, i + 1));
    }

    return additional_quarters;
}

function getBeginningOfNextNthQuarter(base_date: Date, nth: number): Date {
    const base_quarter = new Date(
        base_date.getFullYear(),
        (getQuarterNumber(base_date) - 1) * 3,
        1
    );

    return new Date(new Date(base_quarter).setMonth(base_quarter.getMonth() + nth * 3));
}
