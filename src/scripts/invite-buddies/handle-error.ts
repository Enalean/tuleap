/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { FetchWrapperError } from "tlp";
import { displayError } from "./feedback-display";

export async function handleError(rest_error: FetchWrapperError): Promise<void> {
    if (rest_error.response === undefined) {
        throw rest_error;
    }

    const message = getErrorMessage(await rest_error.response.json());
    if (!message) {
        throw rest_error;
    }

    displayError(message);
    throw rest_error;
}

function getErrorMessage(error_body: {
    readonly error?: {
        readonly code?: number;
        readonly message?: string;
        readonly i18n_error_message?: string;
    };
}): string {
    if (!error_body.error) {
        return "";
    }

    if (error_body.error.i18n_error_message) {
        return error_body.error.i18n_error_message;
    }

    if (error_body.error.code && error_body.error.message) {
        return error_body.error.code + " " + error_body.error.message;
    }

    return "";
}
