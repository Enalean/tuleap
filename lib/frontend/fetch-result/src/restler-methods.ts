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
import type { RetrieveResponse } from "./ResponseRetriever";
import { decodeJSON } from "./json-decoder";
import { getURI } from "./auto-encoder";
import type { PatchMethod, PostMethod, PutMethod } from "./constants";
import { DELETE_METHOD, GET_METHOD, HEAD_METHOD, OPTIONS_METHOD } from "./constants";
import type { EncodedURI } from "./uri-string-template";
import { RestlerErrorHandler } from "./faults/RestlerErrorHandler";
import { credentials, json_headers } from "./headers";
import type { OptionsWithAutoEncodedParameters } from "./options";

export const buildHead =
    (response_retriever: RetrieveResponse) =>
    (uri: EncodedURI, options?: OptionsWithAutoEncodedParameters): ResultAsync<Response, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri, options?.params), {
                method: HEAD_METHOD,
                credentials,
            })
            .andThen(RestlerErrorHandler().handleErrorResponse);

export const buildOptions =
    (response_retriever: RetrieveResponse) =>
    (uri: EncodedURI): ResultAsync<Response, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri), { method: OPTIONS_METHOD, credentials })
            .andThen(RestlerErrorHandler().handleErrorResponse);

export const buildDelete =
    (response_retriever: RetrieveResponse) =>
    (uri: EncodedURI): ResultAsync<Response, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri), { method: DELETE_METHOD, credentials })
            .andThen(RestlerErrorHandler().handleErrorResponse);

export const buildGetJSON =
    (response_retriever: RetrieveResponse) =>
    <TypeOfJSONPayload>(
        uri: EncodedURI,
        options?: OptionsWithAutoEncodedParameters,
    ): ResultAsync<TypeOfJSONPayload, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri, options?.params), { method: GET_METHOD, credentials })
            .andThen(RestlerErrorHandler().handleErrorResponse)
            .andThen(decodeJSON<TypeOfJSONPayload>);

export const buildSendJSON =
    (response_retriever: RetrieveResponse, method: PutMethod | PatchMethod | PostMethod) =>
    (
        uri: EncodedURI,
        options: OptionsWithAutoEncodedParameters,
        json_payload: unknown,
    ): ResultAsync<Response, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri, options.params), {
                method,
                credentials,
                headers: json_headers,
                body: JSON.stringify(json_payload),
            })
            .andThen(RestlerErrorHandler().handleErrorResponse);

export const buildSendAndReceiveJSON =
    (response_retriever: RetrieveResponse, method: PutMethod | PatchMethod | PostMethod) =>
    <TypeOfJSONPayload>(
        uri: EncodedURI,
        json_payload: unknown,
    ): ResultAsync<TypeOfJSONPayload, Fault> =>
        buildSendJSON(response_retriever, method)(uri, {}, json_payload).andThen(
            decodeJSON<TypeOfJSONPayload>,
        );
