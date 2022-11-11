/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

const getMessageFromException = async (exception: unknown): Promise<string | null> => {
    if (!isFetchWrapperError(exception)) {
        return null;
    }

    const response = await exception.response.json();

    if (Object.prototype.hasOwnProperty.call(response, "error")) {
        if (Object.prototype.hasOwnProperty.call(response.error, "i18n_error_message")) {
            return response.error.i18n_error_message;
        }

        return response.error.message;
    }

    return null;
};

function isFetchWrapperError(exception: unknown): exception is FetchWrapperError {
    return typeof exception === "object" && exception !== null && "response" in exception;
}

export { getMessageFromException };
