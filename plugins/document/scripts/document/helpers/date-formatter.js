/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import phptomoment from "phptomoment";

export function formatDateUsingPreferredUserFormat(date, user_preferred_format) {
    return moment(date).format(phptomoment(user_preferred_format));
}

export function getElapsedTimeFromNow(date) {
    return moment(date).fromNow();
}

export const isDateValid = (date) => moment(date).isValid();

export function isToday(date) {
    return moment().diff(date, "days") === 0;
}
