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
import { ResultAsync } from "neverthrow";
import type { FetchInterface } from "./FetchInterface";
import { NetworkFault } from "./faults/NetworkFault";
import type { SupportedHTTPMethod } from "./constants";
import type { EncodedURI } from "./uri-string-template";
import { getEncodedURIString } from "./uri-string-template";

type GeneralOptions = {
    readonly method: SupportedHTTPMethod;
    readonly headers?: Headers;
    readonly credentials?: RequestCredentials;
    readonly mode?: RequestMode;
};

type PostPutPatchOptions = GeneralOptions & {
    readonly method: "POST" | "PUT" | "PATCH";
    readonly body: BodyInit;
};
export type ResponseRetrieverOptions = GeneralOptions | PostPutPatchOptions;

export type RetrieveResponse = {
    retrieveResponse(
        uri: EncodedURI,
        options: ResponseRetrieverOptions
    ): ResultAsync<Response, Fault>;
};

export const ResponseRetriever = (fetcher: FetchInterface): RetrieveResponse => ({
    retrieveResponse(
        uri: EncodedURI,
        options: ResponseRetrieverOptions
    ): ResultAsync<Response, Fault> {
        const init: RequestInit = { ...options };
        const fetch_promise = fetcher.fetch(getEncodedURIString(uri), init);
        return ResultAsync.fromPromise(fetch_promise, NetworkFault.fromError);
    },
});
