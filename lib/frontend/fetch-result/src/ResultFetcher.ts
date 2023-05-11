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
import type { AutoEncodedParameters } from "./auto-encoder";
import { getURI } from "./auto-encoder";
import {
    DELETE_METHOD,
    GET_METHOD,
    HEAD_METHOD,
    OPTIONS_METHOD,
    PATCH_METHOD,
    POST_METHOD,
    PUT_METHOD,
} from "./constants";
import type { EncodedURI } from "./uri-string-template";

export interface OptionsWithAutoEncodedParameters {
    readonly params?: AutoEncodedParameters;
}

export type FetchResult = {
    getJSON<TypeOfJSONPayload>(
        uri: EncodedURI,
        options?: OptionsWithAutoEncodedParameters
    ): ResultAsync<TypeOfJSONPayload, Fault>;

    head(uri: EncodedURI, options?: OptionsWithAutoEncodedParameters): ResultAsync<Response, Fault>;

    options(uri: EncodedURI): ResultAsync<Response, Fault>;

    putJSON<TypeOfJSONPayload>(
        uri: EncodedURI,
        json_payload: unknown
    ): ResultAsync<TypeOfJSONPayload, Fault>;

    patchJSON<TypeOfJSONPayload>(
        uri: EncodedURI,
        json_payload: unknown
    ): ResultAsync<TypeOfJSONPayload, Fault>;

    postJSON<TypeOfJSONPayload>(
        uri: EncodedURI,
        json_payload: unknown
    ): ResultAsync<TypeOfJSONPayload, Fault>;

    post(
        uri: EncodedURI,
        options: OptionsWithAutoEncodedParameters,
        json_payload: unknown
    ): ResultAsync<Response, Fault>;

    put(
        uri: EncodedURI,
        options: OptionsWithAutoEncodedParameters,
        json_payload: unknown
    ): ResultAsync<Response, Fault>;

    patch(
        uri: EncodedURI,
        options: OptionsWithAutoEncodedParameters,
        json_payload: unknown
    ): ResultAsync<Response, Fault>;

    del(uri: EncodedURI): ResultAsync<Response, Fault>;
};

const json_headers = new Headers({ "Content-Type": "application/json" });
const credentials: RequestCredentials = "same-origin";

export const ResultFetcher = (response_retriever: RetrieveResponse): FetchResult => ({
    getJSON: <TypeOfJSONPayload>(uri: EncodedURI, options?: OptionsWithAutoEncodedParameters) =>
        response_retriever
            .retrieveResponse(getURI(uri, options?.params), { method: GET_METHOD, credentials })
            .andThen((response) => decodeJSON<TypeOfJSONPayload>(response)),

    head: (uri, options) =>
        response_retriever.retrieveResponse(getURI(uri, options?.params), {
            method: HEAD_METHOD,
            credentials,
        }),

    options: (uri) =>
        response_retriever.retrieveResponse(getURI(uri), { method: OPTIONS_METHOD, credentials }),

    putJSON: <TypeOfJSONPayload>(uri: EncodedURI, json_payload: unknown) =>
        response_retriever
            .retrieveResponse(getURI(uri), {
                method: PUT_METHOD,
                credentials,
                headers: json_headers,
                body: JSON.stringify(json_payload),
            })
            .andThen((response) => decodeJSON<TypeOfJSONPayload>(response)),

    patchJSON: <TypeOfJSONPayload>(uri: EncodedURI, json_payload: unknown) =>
        response_retriever
            .retrieveResponse(getURI(uri), {
                method: PATCH_METHOD,
                credentials,
                headers: json_headers,
                body: JSON.stringify(json_payload),
            })
            .andThen((response) => decodeJSON<TypeOfJSONPayload>(response)),

    postJSON: <TypeOfJSONPayload>(uri: EncodedURI, json_payload: unknown) =>
        response_retriever
            .retrieveResponse(getURI(uri), {
                method: POST_METHOD,
                credentials,
                headers: json_headers,
                body: JSON.stringify(json_payload),
            })
            .andThen((response) => decodeJSON<TypeOfJSONPayload>(response)),

    post: (uri: EncodedURI, options: OptionsWithAutoEncodedParameters, json_payload: unknown) =>
        response_retriever.retrieveResponse(getURI(uri, options.params), {
            method: POST_METHOD,
            credentials,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    put: (uri: EncodedURI, options: OptionsWithAutoEncodedParameters, json_payload: unknown) =>
        response_retriever.retrieveResponse(getURI(uri, options.params), {
            method: PUT_METHOD,
            credentials,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    patch: (uri: EncodedURI, options: OptionsWithAutoEncodedParameters, json_payload: unknown) =>
        response_retriever.retrieveResponse(getURI(uri, options.params), {
            method: PATCH_METHOD,
            credentials,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    del: (uri) =>
        response_retriever.retrieveResponse(getURI(uri), { method: DELETE_METHOD, credentials }),
});
