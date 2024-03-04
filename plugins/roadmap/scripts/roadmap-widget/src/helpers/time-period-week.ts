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
import { getBeginningOfPeriod, getEndOfPeriod } from "./begin-end-of-period";
import type { DateTime } from "luxon";

export class TimePeriodWeek implements TimePeriod {
    readonly weeks: DateTime[];
    private readonly gettext_provider: VueGettextProvider;

    constructor(
        readonly from: DateTime,
        readonly to: DateTime,
        gettext_provider: VueGettextProvider,
    ) {
        this.gettext_provider = gettext_provider;
        this.weeks = getWeeks(from, to);
    }

    get units(): DateTime[] {
        return this.weeks;
    }

    additionalUnits(nb: number): DateTime[] {
        if (nb <= 0) {
            return [];
        }

        const latest_week = this.weeks[this.weeks.length - 1];
        const next_week = getBeginningOfNextWeek(latest_week);
        return getBeginningOfNextNthWeeks(next_week, nb - 1);
    }

    formatLong(unit: DateTime): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Week %{ week } of %{ year }"),
            {
                week: unit.weekNumber,
                year: unit.year,
            },
        );
    }

    formatShort(unit: DateTime): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("W%{ week }"),
            {
                week: unit.weekNumber,
            },
        );
    }

    getEvenOddClass(unit: DateTime): string {
        return unit.month % 2 === 1 ? "even" : "odd";
    }
}

function getWeeks(start: DateTime, end: DateTime): DateTime[] {
    const beginning_of_period = getBeginningOfPeriod(start, end);
    const end_of_period = getEndOfPeriod(start, end);

    const start_of_first_week = getBeginningOfCurrentWeek(beginning_of_period);
    const start_of_last_week = getBeginningOfNextWeek(end_of_period);

    return getBeginningOfNextNthWeeks(
        start_of_first_week,
        getDateDiffInWeeks(start_of_first_week, start_of_last_week),
    );
}

function getBeginningOfNextWeek(current_week_start: DateTime): DateTime {
    return current_week_start.startOf("week").plus({ week: 1 });
}

function getBeginningOfNextNthWeeks(base_week_start: DateTime, nth: number): DateTime[] {
    const weeks = [base_week_start];
    let last_week = base_week_start;

    for (let i = 0; i < nth; i++) {
        const next_week_start = getBeginningOfNextWeek(last_week);
        weeks.push(next_week_start);

        last_week = next_week_start;
    }

    return weeks;
}

function getBeginningOfCurrentWeek(base_date: DateTime): DateTime {
    return base_date.startOf("week");
}

function getDateDiffInWeeks(start_date: DateTime, end_date: DateTime): number {
    return end_date.diff(start_date, "weeks").weeks;
}
