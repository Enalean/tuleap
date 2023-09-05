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
import currentWeekNumber from "current-week-number";

export class TimePeriodWeek implements TimePeriod {
    readonly weeks: Date[];
    private readonly gettext_provider: VueGettextProvider;

    constructor(
        readonly from: Date,
        readonly to: Date,
        gettext_provider: VueGettextProvider,
    ) {
        this.gettext_provider = gettext_provider;
        this.weeks = getWeeks(from, to);
    }

    get units(): Date[] {
        return this.weeks;
    }

    additionalUnits(nb: number): Date[] {
        if (nb <= 0) {
            return [];
        }

        const latest_week = this.weeks[this.weeks.length - 1];
        const next_week = getBeginningOfNextWeek(latest_week);
        return getBeginningOfNextNthWeeks(next_week, nb - 1);
    }

    formatLong(unit: Date): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Week %{ week } of %{ year }"),
            {
                week: currentWeekNumber(unit),
                year: unit.getUTCFullYear(),
            },
        );
    }

    formatShort(unit: Date): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("W%{ week }"),
            {
                week: currentWeekNumber(unit),
            },
        );
    }

    getEvenOddClass(unit: Date): string {
        return unit.getUTCMonth() % 2 === 0 ? "even" : "odd";
    }
}

function getWeeks(start: Date, end: Date): Date[] {
    const beginning_of_period = getBeginningOfPeriod(start, end);
    const end_of_period = getEndOfPeriod(start, end);

    const start_of_first_week = getBeginningOfCurrentWeek(beginning_of_period);
    const start_of_last_week = getBeginningOfNextWeek(end_of_period);

    return getBeginningOfNextNthWeeks(
        start_of_first_week,
        getDateDiffInWeeks(start_of_first_week, start_of_last_week),
    );
}

function getBeginningOfNextWeek(current_week_start: Date): Date {
    const next_week_start = new Date(current_week_start);
    const NB_DAYS_IN_A_WEEK = 7;

    next_week_start.setUTCDate(next_week_start.getUTCDate() + NB_DAYS_IN_A_WEEK);
    next_week_start.setUTCHours(0, 0, 0);

    return next_week_start;
}

function getBeginningOfNextNthWeeks(base_week_start: Date, nth: number): Date[] {
    const weeks = [base_week_start];
    let last_week = base_week_start;

    for (let i = 0; i < nth; i++) {
        const next_week_start = getBeginningOfNextWeek(last_week);
        weeks.push(next_week_start);

        last_week = next_week_start;
    }

    return weeks;
}

function getBeginningOfCurrentWeek(base_date: Date): Date {
    const first_day_of_week = new Date(base_date);
    const current_day_of_week = base_date.getUTCDay();
    const MONDAY_INDEX_IN_WEEK = 1;

    first_day_of_week.setUTCDate(
        first_day_of_week.getUTCDate() - current_day_of_week + MONDAY_INDEX_IN_WEEK,
    );
    first_day_of_week.setUTCHours(0, 0, 0);

    return first_day_of_week;
}

function getDateDiffInWeeks(start_date: Date, end_date: Date): number {
    const MILLISECONDS_PER_DAY = 1000 * 60 * 60 * 24;
    const NB_DAYS_IN_A_WEEK = 7;

    const start_utc = Date.UTC(
        start_date.getFullYear(),
        start_date.getMonth(),
        start_date.getDate(),
    );
    const end_utc = Date.UTC(end_date.getFullYear(), end_date.getMonth(), end_date.getDate());

    return Math.floor((end_utc - start_utc) / MILLISECONDS_PER_DAY / NB_DAYS_IN_A_WEEK);
}
