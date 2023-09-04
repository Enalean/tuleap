/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import { formatFromPhpToMoment } from "@tuleap/date-helper";

export function formatDateUsingPreferredUserFormat(
    date: string,
    user_preferred_format: string,
): string {
    return moment(date).format(formatFromPhpToMoment(user_preferred_format));
}

export const isDateValid = (date: string): boolean => moment(date).isValid();

export function isToday(date: string): boolean {
    return moment().diff(date, "days") === 0;
}
