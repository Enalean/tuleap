/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { FetchWrapperError } from "@tuleap/tlp-fetch";

export function extractErrorMessage(error: Error): Promise<string> {
    if (isFetchWrapperError(error)) {
        return error.response.json().then(
            (json_response) => {
                if ("error" in json_response && "message" in json_response.error) {
                    return json_response.error.message;
                }
                return Promise.resolve(error.message);
            },
            () => {
                return Promise.resolve(error.message);
            }
        );
    }
    return Promise.resolve(error.message);
}

function isFetchWrapperError(error: Error): error is FetchWrapperError {
    return "response" in error;
}
