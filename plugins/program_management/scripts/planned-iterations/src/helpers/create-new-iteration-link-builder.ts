/*
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

export function buildIterationCreationUrl(
    program_increment_id: number,
    iteration_tracker_id: number,
): string {
    const url_params = new URLSearchParams({
        "redirect-to-planned-iterations": "create",
        "increment-id": String(program_increment_id),
        tracker: String(iteration_tracker_id),
        func: "new-artifact",
    });

    return `/plugins/tracker/?${url_params.toString()}`;
}

export function buildIterationEditionUrl(
    iteration_id: number,
    program_increment_id: number,
): string {
    const url_params = new URLSearchParams({
        aid: String(iteration_id),
        "redirect-to-planned-iterations": "update",
        "increment-id": String(program_increment_id),
    });

    return `/plugins/tracker/?${url_params.toString()}`;
}
