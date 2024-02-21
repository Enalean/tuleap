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

export class TimePeriodQuarter implements TimePeriod {
    private readonly quarters: DateTime[];
    private gettext_provider: VueGettextProvider;

    constructor(
        readonly from: DateTime,
        readonly to: DateTime,
        gettext_provider: VueGettextProvider,
    ) {
        this.gettext_provider = gettext_provider;
        this.quarters = getQuarters(from, to);
    }

    get units(): DateTime[] {
        return this.quarters;
    }

    formatShort(unit: DateTime): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Q%{ quarter }"),
            {
                quarter: unit.quarter,
            },
        );
    }

    formatLong(unit: DateTime): string {
        return this.gettext_provider.$gettextInterpolate(
            this.gettext_provider.$gettext("Quarter %{ quarter } of %{ year }"),
            {
                quarter: unit.quarter,
                year: unit.year,
            },
        );
    }

    additionalUnits(nb: number): DateTime[] {
        return getAdditionalQuarters(this.quarters[this.quarters.length - 1], nb);
    }

    getEvenOddClass(): string {
        return "";
    }
}

function getQuarters(start: DateTime, end: DateTime): DateTime[] {
    const beginning_of_period = getBeginningOfPeriod(start, end);
    const end_of_period = getEndOfPeriod(start, end);

    const starting_quarter = getBeginningOfNextNthQuarter(beginning_of_period, 0);
    const quarters = [starting_quarter];

    let i = 1;
    while (quarters[quarters.length - 1] < end_of_period) {
        quarters.push(getBeginningOfNextNthQuarter(starting_quarter, i++));
    }

    return quarters;
}

function getAdditionalQuarters(base_quarter: DateTime, nb_missing_quarters: number): DateTime[] {
    const additional_quarters: DateTime[] = [];

    if (nb_missing_quarters <= 0) {
        return additional_quarters;
    }

    for (let i = 0; i < nb_missing_quarters; i++) {
        additional_quarters.push(getBeginningOfNextNthQuarter(base_quarter, i + 1));
    }

    return additional_quarters;
}

function getBeginningOfNextNthQuarter(base_date: DateTime, nth: number): DateTime {
    return base_date.startOf("quarter").plus({ quarter: nth });
}
