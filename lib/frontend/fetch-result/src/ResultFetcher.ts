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
import type { GetAll } from "./AllGetter";
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

export interface OptionsWithAutoEncodedParameters {
    readonly params?: AutoEncodedParameters;
}

export type FetchResult = {
    getJSON<TypeOfJSONPayload>(
        uri: string,
        options?: OptionsWithAutoEncodedParameters
    ): ResultAsync<TypeOfJSONPayload, Fault>;

    head(uri: string, options?: OptionsWithAutoEncodedParameters): ResultAsync<Response, Fault>;

    options(uri: string): ResultAsync<Response, Fault>;

    putJSON(uri: string, json_payload: unknown): ResultAsync<Response, Fault>;

    patchJSON(uri: string, json_payload: unknown): ResultAsync<Response, Fault>;

    postJSON(uri: string, json_payload: unknown): ResultAsync<Response, Fault>;

    del(uri: string): ResultAsync<Response, Fault>;
} & GetAll;

const json_headers = new Headers({ "Content-Type": "application/json" });

export const ResultFetcher = (
    response_retriever: RetrieveResponse,
    all_getter: GetAll
): FetchResult => ({
    getJSON: <TypeOfJSONPayload>(uri: string, options?: OptionsWithAutoEncodedParameters) =>
        response_retriever
            .retrieveResponse(getURI(uri, options?.params), { method: GET_METHOD })
            .andThen((response) => decodeJSON<TypeOfJSONPayload>(response)),

    getAllJSON: all_getter.getAllJSON,

    head: (uri, options) =>
        response_retriever.retrieveResponse(getURI(uri, options?.params), { method: HEAD_METHOD }),

    options: (uri) => response_retriever.retrieveResponse(getURI(uri), { method: OPTIONS_METHOD }),

    putJSON: (uri, json_payload) =>
        response_retriever.retrieveResponse(getURI(uri), {
            method: PUT_METHOD,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    patchJSON: (uri, json_payload) =>
        response_retriever.retrieveResponse(getURI(uri), {
            method: PATCH_METHOD,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    postJSON: (uri, json_payload) =>
        response_retriever.retrieveResponse(getURI(uri), {
            method: POST_METHOD,
            headers: json_headers,
            body: JSON.stringify(json_payload),
        }),

    del: (uri) => response_retriever.retrieveResponse(getURI(uri), { method: DELETE_METHOD }),
});
