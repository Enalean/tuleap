/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export default function formatRelativeDate(
    locale: string,
    date: Date,
    reference_date: Date,
): string {
    const diff = reference_date.getTime() - date.getTime();
    const sign = diff >= 0 ? -1 : +1;

    const diff_in_ms = Math.abs(diff);
    const diff_in_minutes = diff_in_ms / (60 * 1000);

    const a_day_in_minutes = 24 * 60;
    const a_month_in_minutes = 30 * 24 * 60;
    const a_year_in_minutes = 12 * 30 * 24 * 60;

    if (diff_in_ms <= 59000) {
        return new Intl.RelativeTimeFormat(locale).format(
            sign * Math.round(diff_in_ms / 1000),
            "seconds",
        );
    } else if (diff_in_minutes <= 44) {
        return new Intl.RelativeTimeFormat(locale).format(
            sign * Math.round(diff_in_minutes),
            "minutes",
        );
    } else if (diff_in_minutes < a_day_in_minutes) {
        return new Intl.RelativeTimeFormat(locale).format(
            sign * Math.round(diff_in_minutes / 60),
            "hours",
        );
    } else if (diff_in_minutes < a_month_in_minutes) {
        return new Intl.RelativeTimeFormat(locale).format(
            sign * Math.round(diff_in_minutes / a_day_in_minutes),
            "days",
        );
    } else if (diff_in_minutes < a_year_in_minutes) {
        return new Intl.RelativeTimeFormat(locale).format(
            sign * Math.round(diff_in_minutes / a_month_in_minutes),
            "months",
        );
    }
    return new Intl.RelativeTimeFormat(locale).format(
        sign * Math.round(diff_in_minutes / a_year_in_minutes),
        "years",
    );
}
