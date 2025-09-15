/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { ERROR_TYPE_NO_GIT, ERROR_TYPE_UNKNOWN_ERROR } from "../constants";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

export async function getErrorCode(e: unknown): Promise<number> {
    let error_code;

    if (!(e instanceof FetchWrapperError)) {
        throw e;
    }

    try {
        const { error } = await e.response.json();
        error_code = Number.parseInt(error.code, 10);
    } catch (_e) {
        return ERROR_TYPE_UNKNOWN_ERROR;
    }

    if (error_code === 404) {
        return ERROR_TYPE_NO_GIT;
    }

    return ERROR_TYPE_UNKNOWN_ERROR;
}
