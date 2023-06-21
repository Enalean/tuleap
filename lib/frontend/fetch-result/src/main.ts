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
import type { Fault } from "@tuleap/fault";
import { AllGetter } from "./AllGetter";
import type { RetrieveResponse } from "./ResponseRetriever";
import { ResponseRetriever } from "./ResponseRetriever";
import {
    buildDelete,
    buildGetJSON,
    buildHead,
    buildOptions,
    buildSendAndReceiveJSON,
    buildSendJSON,
} from "./restler-methods";
import { RestlerErrorHandler } from "./faults/RestlerErrorHandler";
import { PATCH_METHOD, POST_METHOD, PUT_METHOD } from "./constants";
import { buildSendFormAndReceiveText } from "./text-methods";

export type { GetAllOptions, GetAllCollectionCallback } from "./AllGetter";
export type { OptionsWithAutoEncodedParameters } from "./options";
export type { RetrieveResponse, ResponseRetrieverOptions } from "./ResponseRetriever";
export type { ErrorResponseHandler } from "./faults/ErrorResponseHandler";

const response_retriever = ResponseRetriever(window);
const restler_response_retriever: RetrieveResponse = {
    retrieveResponse: (...args) =>
        response_retriever
            .retrieveResponse(...args)
            .andThen(RestlerErrorHandler().handleErrorResponse),
};
const all_getter = AllGetter(restler_response_retriever);

// Define an unused type alias just so we can import ResultAsync and Fault types for the doc-blocks.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
type _Unused = ResultAsync<never, Fault>;

export { decodeJSON } from "./json-decoder";
export { JSONParseFault } from "./faults/JSONParseFault";
export { ResponseRetriever } from "./ResponseRetriever";
export type { EncodedURI } from "./uri-string-template";
export { uri, rawUri } from "./uri-string-template";

/**
 * `getJSON` returns a `ResultAsync<TypeOfJSONPayload, Fault>` with `TypeOfJSONPayload` supplied as a generic type.
 * It queries the given URI with GET method, decodes the response into JSON and returns an `Ok` variant containing the
 * JSON payload.
 * If there was a problem (network error, remote API error, JSON parsing error), it returns an `Err` variant
 * containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @template TypeOfJSONPayload
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {OptionsWithAutoEncodedParameters=} options (optional) An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * @returns {ResultAsync<TypeOfJSONPayload, Fault>}
 */
export const getJSON = buildGetJSON(response_retriever);

/**
 * `getAllJSON` returns a `ResultAsync<ReadonlyArray<TypeOfArrayItem>, Fault>` with `TypeOfArrayItem` supplied as a
 * generic type. It is useful to query paginated endpoints that expect `limit` and `offset` search parameters.
 *
 * It queries the given URI with GET method once and reads the `X-PAGINATION-SIZE` header to figure out the total
 * number of elements to retrieve. Then, it queries it again (with GET method) with parallel requests until all elements
 * have been retrieved. It decodes each response into JSON and calls the `getCollectionCallback` (if supplied) for each
 * batch.
 *
 * `getCollectionCallback` is useful to handle differences in the endpoint JSON response shapes. Some endpoints will
 * return directly an array (`[item1, item2]`), which is what the default callback expects. Other endpoints will return
 * a JSON object containing an array (`{ collection: [item1, item2] }`). The callback receives the JSON payload
 * (with `TypeOfJSONPayload` type) and must convert it to an array of `TypeOfArrayItem`.
 *
 * The callback can also be used to do progressive display, where you display each batch of items as it arrives.
 *
 * `params` are added to the URI for each request. The only exception is `offset`, which is computed for each request
 * until all elements have been retrieved. If `limit` and `offset` `params` are not supplied, it defaults
 * `limit` to 100 and `offset` to 0.
 *
 * It is possible to control the number of parallel requests by providing a `max_parralel_requests` key in `options`.
 * Default value is 6 parallel requests.
 *
 * `getAllJSON` returns an `Ok` variant containing a single, flat array of `TypeOfArrayItem`.
 * If there was a problem in any of the requests (network error, remote API error, JSON parsing error), it returns
 * an `Err` variant containing a `Fault` for the first problem it encounters.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @template TypeOfArrayItem
 * @template TypeOfJSONPayload
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {GetAllOptions=} options (optional) An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * `params` has two special keys: `limit` and `offset`. `limit` controls the number of items fetched at each
 * request, `offset` determines the starting point. `limit` defaults to 100, and `offset` to 0.
 * `options` also has a `getCollectionCallback` function, if supplied it allows to unwrap `TypeOfJSONPayload` into an
 * array of `TypeOfArrayItem`.
 * `options` also has a `max_parallel_requests` key, if supplied it controls the number of parallel requests in-flight
 * at the same time. Defaults to 6.
 * @returns {ResultAsync<ReadonlyArray<TypeOfArrayItem>, Fault>}
 */
export const getAllJSON = all_getter.getAllJSON;

/**
 * `head` queries the given URI with HEAD method and returns an `Ok` variant containing a Response.
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {OptionsWithAutoEncodedParameters=} options (optional) An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * @returns {ResultAsync<Response, Fault>}
 */
export const head = buildHead(response_retriever);

/**
 * `options` queries the given URI with OPTIONS method and returns an `Ok` variant containing a Response.
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @returns {ResultAsync<Response, Fault>}
 */
export const options = buildOptions(response_retriever);

/**
 * `putJSON` queries the given URI with PUT method and returns a `ResultAsync<TypeOfJSONPayload, Fault>`
 * with `TypeOfJSONPayload` supplied as a generic type.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error, JSON parsing error), it returns an `Err` variant
 * containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @template TypeOfJSONPayload
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<TypeOfJSONPayload, Fault>}
 */
export const putJSON = buildSendAndReceiveJSON(response_retriever, PUT_METHOD);

/**
 * `patchJSON` queries the given URI with PATCH method and returns a `ResultAsync<TypeOfJSONPayload, Fault>`
 * with `TypeOfJSONPayload` supplied as a generic type.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error, JSON parsing error), it returns an `Err` variant
 * containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @template TypeOfJSONPayload
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<TypeOfJSONPayload, Fault>}
 */
export const patchJSON = buildSendAndReceiveJSON(response_retriever, PATCH_METHOD);

/**
 * `postJSON` queries the given URI with POST method and returns a `ResultAsync<TypeOfJSONPayload, Fault>`
 * with `TypeOfJSONPayload` supplied as a generic type.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error, JSON parsing error), it returns an `Err` variant
 * containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @template TypeOfJSONPayload
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<TypeOfJSONPayload, Fault>}
 */
export const postJSON = buildSendAndReceiveJSON(response_retriever, POST_METHOD);

/**
 * `post` queries the given URI with POST method and returns an `Ok` variant containing a Response.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {OptionsWithAutoEncodedParameters} options An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<Response, Fault>}
 */
export const post = buildSendJSON(response_retriever, POST_METHOD);

/**
 * `put` queries the given URI with PUT method and returns an `Ok` variant containing a Response.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {OptionsWithAutoEncodedParameters} options An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<Response, Fault>}
 */
export const put = buildSendJSON(response_retriever, PUT_METHOD);

/**
 * `patch` queries the given URI with PATCH method and returns an `Ok` variant containing a Response.
 * It automatically sets the "Content-type" header to "application/json".
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {OptionsWithAutoEncodedParameters} options An object with a `params` key containing a list of URI
 * search parameters. Each key-value pair will be URI-encoded and appended to `uri`.
 * @param {unknown} json_payload The JSON payload to send in the request body. It is automatically encoded as a JSON
 * string.
 * @returns {ResultAsync<Response, Fault>}
 */
export const patch = buildSendJSON(response_retriever, PATCH_METHOD);

/**
 * `del` queries the given URI with DELETE method and returns an `Ok` variant containing a Response.
 * If there was a problem (network error, remote API error), it returns an `Err` variant containing a `Fault`.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @returns {ResultAsync<Response, Fault>}
 */
export const del = buildDelete(response_retriever);

/**
 * `postFormWithTextResponse` sends a `FormData` body with POST method and returns an `Ok` variant containing the
 * response text.
 * It automatically sets the "Content-Type" header to "application/x-www-form-urlencoded".
 * If there was a problem (network error, remote API error, Text parsing error), it returns an `Err` variant
 * containing a `Fault` with the response text.
 *
 * Each type of Fault has a dedicated method to distinguish them in error-handling, please see the README for more details.
 *
 * @param {EncodedURI} uri The URI destination of the request.
 * @param {FormData} payload The Form payload to send in the request body.
 * @returns {ResultAsync<string, Fault>}
 */
export const postFormWithTextResponse = buildSendFormAndReceiveText(
    response_retriever,
    POST_METHOD
);
