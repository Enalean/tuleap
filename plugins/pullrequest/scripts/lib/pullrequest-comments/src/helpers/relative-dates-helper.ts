/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import moment from "moment";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";

export interface HelpRelativeDatesDisplay {
    getRelativeDatePreference: () => string;
    getRelativeDatePlacement: () => string;
    getUserLocale: () => string;
    getFormattedDateUsingPreferredUserFormat: (date: string) => string;
}

export const RelativeDatesHelper = (
    date_format: string,
    relative_date_display: RelativeDatesDisplayPreference,
    user_locale: string,
): HelpRelativeDatesDisplay => ({
    getRelativeDatePreference: (): string => relativeDatePreference(relative_date_display),
    getRelativeDatePlacement: (): string => relativeDatePlacement(relative_date_display, "right"),
    getUserLocale: (): string => user_locale,
    getFormattedDateUsingPreferredUserFormat: (date: string): string =>
        moment(date).format(formatFromPhpToMoment(date_format)),
});
