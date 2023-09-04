/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { okAsync, errAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { decodeJSON } from "../json-decoder";
import { TuleapAPIFault } from "./TuleapAPIFault";
import type { ErrorResponseHandler } from "./ErrorResponseHandler";

type RestlerErrorMessage = {
    readonly message?: string;
    readonly i18n_error_message?: string;
};

type RestlerError = {
    readonly error?: RestlerErrorMessage;
};

const convertRestlerErrorResponseToFault = (response: Response): ResultAsync<never, Fault> =>
    decodeJSON<RestlerError>(response).andThen((error_json) => {
        if (error_json.error !== undefined) {
            if (error_json.error.i18n_error_message !== undefined) {
                return errAsync(
                    TuleapAPIFault.fromCodeAndMessage(
                        response.status,
                        error_json.error.i18n_error_message,
                    ),
                );
            }
            if (error_json.error.message !== undefined) {
                return errAsync(
                    TuleapAPIFault.fromCodeAndMessage(response.status, error_json.error.message),
                );
            }
        }
        return errAsync(TuleapAPIFault.fromCodeAndMessage(response.status, response.statusText));
    });

export const RestlerErrorHandler = (): ErrorResponseHandler => ({
    handleErrorResponse: (response): ResultAsync<Response, Fault> =>
        response.ok ? okAsync(response) : convertRestlerErrorResponseToFault(response),
});
