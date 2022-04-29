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

import type { Fault } from "@tuleap/fault";
import { ResultAsync, okAsync, errAsync } from "neverthrow";
import type { FetchInterface } from "./FetchInterface";
import { NetworkFault } from "./NetworkFault";
import { TuleapAPIFault } from "./TuleapAPIFault";
import { decodeJSON } from "./json-decoder";
import type { PatchMethod, PostMethod, PutMethod, SupportedHTTPMethod } from "./constants";

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
                        error_json.error.i18n_error_message
                    )
                );
            }
            if (error_json.error.message !== undefined) {
                return errAsync(
                    TuleapAPIFault.fromCodeAndMessage(response.status, error_json.error.message)
                );
            }
        }
        return errAsync(TuleapAPIFault.fromCodeAndMessage(response.status, response.statusText));
    });

type GeneralOptions = {
    readonly method: SupportedHTTPMethod;
};

type PostPutPatchOptions = {
    readonly method: PostMethod | PutMethod | PatchMethod;
    readonly headers: Headers;
    readonly body: BodyInit;
};
type ResponseRetrieverOptions = GeneralOptions | PostPutPatchOptions;

export type RetrieveResponse = {
    retrieveResponse(uri: string, options: ResponseRetrieverOptions): ResultAsync<Response, Fault>;
};

export const ResponseRetriever = (fetcher: FetchInterface): RetrieveResponse => ({
    retrieveResponse(uri: string, options: ResponseRetrieverOptions): ResultAsync<Response, Fault> {
        const credentials = "same-origin";

        const init: RequestInit = { method: options.method, credentials };
        if ("headers" in options) {
            init.headers = options.headers;
            init.body = options.body;
        }
        const fetch_promise = fetcher.fetch(uri, init);
        return ResultAsync.fromPromise(fetch_promise, NetworkFault.fromError).andThen(
            (response: Response) => {
                if (!response.ok) {
                    return convertRestlerErrorResponseToFault(response);
                }
                return okAsync(response);
            }
        );
    },
});
