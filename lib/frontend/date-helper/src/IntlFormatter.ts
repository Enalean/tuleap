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

import type { LocaleString } from "@tuleap/core-constants";
import { fr_FR_LOCALE } from "@tuleap/core-constants";

function toBCP47(locale: string): string {
    return locale.replace("_", "-");
}

function formatToPseudoISO(formatter: Intl.DateTimeFormat, date_string: string): string {
    const initial_accumulator = {
        year: "",
        month: "",
        day: "",
        hour: "",
        minute: "",
    };
    const parts = formatter.formatToParts(new Date(date_string)).reduce((accumulator, part) => {
        if (part.type === "year") {
            accumulator.year = part.value;
        }
        if (part.type === "month") {
            accumulator.month = "-" + part.value;
        }
        if (part.type === "day") {
            accumulator.day = "-" + part.value;
        }
        if (part.type === "hour") {
            accumulator.hour = " " + part.value;
        }
        if (part.type === "minute") {
            accumulator.minute = ":" + part.value;
        }
        return accumulator;
    }, initial_accumulator);
    return `${parts.year}${parts.month}${parts.day}${parts.hour}${parts.minute}`;
}

export type DateFormatType = "date-with-time" | "date" | "short-month";

function buildOptions(timezone: string, type: DateFormatType): Intl.DateTimeFormatOptions {
    const options: Intl.DateTimeFormatOptions = {
        timeZone: timezone,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    };
    if (type === "date") {
        return options;
    }
    if (type === "date-with-time") {
        return { ...options, hour: "2-digit", minute: "2-digit", hour12: false };
    }
    return { timeZone: timezone, year: "numeric", month: "short", day: "numeric" };
}

export type IntlFormatter = {
    /**
     * Returns `date_string` formatted according to the configured locale, timezone and date/time format type.
     */
    format(date_string: string | null | undefined): string;
};

/**
 * Builds a formatter configured for the given user locale, user timezone and date/time format type.
 */
export const IntlFormatter = (
    locale: LocaleString,
    timezone: string,
    type: DateFormatType,
): IntlFormatter => {
    const formatter = Intl.DateTimeFormat(toBCP47(locale), buildOptions(timezone, type));

    return {
        format(date_string): string {
            if (!date_string || date_string === "") {
                return "";
            }
            if (locale === fr_FR_LOCALE || type === "short-month") {
                return formatter.format(new Date(date_string));
            }
            return formatToPseudoISO(formatter, date_string);
        },
    };
};
