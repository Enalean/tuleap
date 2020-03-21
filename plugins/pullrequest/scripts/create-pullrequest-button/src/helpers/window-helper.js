/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export function redirectTo(new_href) {
    const parser = document.createElement("a");
    parser.href = new_href;

    const original_request_uri = window.location.pathname + window.location.search;
    const new_request_uri = parser.pathname + parser.search;
    const are_request_uri_the_same = original_request_uri === new_request_uri;

    window.location.href = new_href;
    if (are_request_uri_the_same) {
        window.location.reload();
    }
}
