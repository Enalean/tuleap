/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { DocumentJsonError } from "../../type";

export function getErrorMessage(error_json: DocumentJsonError): string {
    if (Object.prototype.hasOwnProperty.call(error_json, "error")) {
        if (Object.prototype.hasOwnProperty.call(error_json.error, "i18n_error_message")) {
            return error_json.error.i18n_error_message;
        }

        return error_json.error.message;
    }

    return "";
}

export async function handleErrorForHistoryVersion(exception: unknown): Promise<string> {
    if (!(exception instanceof FetchWrapperError) || exception.response === undefined) {
        throw exception;
    }
    try {
        const json = await exception.response.json();
        return getErrorMessage(json);
    } catch (error) {
        return "Internal server error";
    }
}
