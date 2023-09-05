/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

type AutoEncodedParameter = [string, string | number | boolean];
interface AutoEncodedParameters {
    [key: string]: string | number | boolean;
}

interface InitWithAutoEncodedParameters {
    params?: AutoEncodedParameters;
}

type GetInit = RequestInit & InitWithAutoEncodedParameters & { method?: "GET" };

const encodeParamToURI = ([key, value]: AutoEncodedParameter): string => {
    return encodeURIComponent(key) + "=" + encodeURIComponent(value);
};

const encodeAllParamsToURI = (params: AutoEncodedParameters): string => {
    let url_params = "";
    const [first_param, ...other_params] = Object.entries(params);

    url_params += "?" + encodeParamToURI(first_param);

    for (const param of other_params) {
        url_params += "&" + encodeParamToURI(param);
    }

    return url_params;
};

const isEmptyObject = (params: Record<string, unknown>): boolean =>
    Object.keys(params).length === 0 && params.constructor === Object;

export async function get(url: string, init: GetInit = {}): Promise<Response> {
    const method = "GET";
    const { credentials = "same-origin", params } = init;

    let url_with_params = url;
    if (params && !isEmptyObject(params)) {
        url_with_params += encodeAllParamsToURI(params);
    }

    const response = await fetch(url_with_params, {
        method,
        credentials,
        ...init,
    });
    return checkResponse(response);
}

interface RecursiveGetLimitParameters {
    limit?: number;
    offset?: number;
}

export type RecursiveGetCollectionCallback<TypeOfJSONPayload, TypeOfArrayItem> = (
    json: TypeOfJSONPayload,
) => Array<TypeOfArrayItem>;

export interface RecursiveGetInit<TypeOfJSONPayload, TypeOfArrayItem> {
    method?: "GET";
    params?: AutoEncodedParameters & RecursiveGetLimitParameters;
    getCollectionCallback?: RecursiveGetCollectionCallback<TypeOfJSONPayload, TypeOfArrayItem>;
}

function defaultGetCollectionCallback<TypeOfArrayItem>(
    json: TypeOfArrayItem,
): Array<TypeOfArrayItem> {
    if (json instanceof Array) {
        return json;
    }
    return [json];
}

export async function recursiveGet<TypeOfJSONPayload, TypeOfArrayItem>(
    url: string,
    init: RequestInit & RecursiveGetInit<TypeOfJSONPayload, TypeOfArrayItem> = {},
    max_parallel_requests = 6,
): Promise<Array<TypeOfArrayItem>> {
    if (max_parallel_requests < 1) {
        throw new Error("At least one request needs to be sent to retrieve data");
    }
    const { params = {}, getCollectionCallback = defaultGetCollectionCallback } = init;

    const { limit = 100, offset = 0 } = params;

    const response = await get(url, {
        ...init,
        params: {
            ...params,
            limit,
            offset,
        },
    });
    const json = await response.json();
    const results = getCollectionCallback(json);

    const pagination_size = response.headers.get("X-PAGINATION-SIZE");
    if (pagination_size === null) {
        throw new Error("No X-PAGINATION-SIZE field in the header.");
    }
    const total = Number.parseInt(pagination_size, 10);

    const all_responses = await limitConcurrencyPool(
        max_parallel_requests,
        [...getAdditionalOffsets(offset, limit, total)],
        async (new_offset) => {
            const new_init = {
                ...init,
                params: {
                    ...params,
                    limit,
                    offset: new_offset,
                },
            };

            const response = await get(url, new_init);
            const json = await response.json();
            return getCollectionCallback(json);
        },
    );

    return all_responses.reduce((accumulator, response) => accumulator.concat(response), results);
}

function* getAdditionalOffsets(offset: number, limit: number, total: number): Generator<number> {
    let new_offset = offset;
    while (new_offset + limit < total) {
        new_offset += limit;
        yield new_offset;
    }
    return new_offset;
}

type PutInit = RequestInit & { method?: "PUT" };
export function put(url: string, init: PutInit = {}): Promise<Response> {
    const method = "PUT",
        { credentials = "same-origin" } = init;

    return fetch(url, { method, credentials, ...init }).then(checkResponse);
}

type PatchInit = RequestInit & { method?: "PATCH" };
export function patch(url: string, init: PatchInit = {}): Promise<Response> {
    const method = "PATCH",
        { credentials = "same-origin" } = init;

    return fetch(url, { method, credentials, ...init }).then(checkResponse);
}

type PostInit = RequestInit & { method?: "POST" };
export function post(url: string, init: PostInit = {}): Promise<Response> {
    const method = "POST",
        { credentials = "same-origin" } = init;

    return fetch(url, { method, credentials, ...init }).then(checkResponse);
}

type DeleteInit = RequestInit & { method?: "DELETE" };
export function del(url: string, init: DeleteInit = {}): Promise<Response> {
    const method = "DELETE",
        { credentials = "same-origin" } = init;

    return fetch(url, { method, credentials, ...init }).then(checkResponse);
}

type OptionsInit = RequestInit & { method?: "OPTIONS" };
export function options(url: string, init: OptionsInit = {}): Promise<Response> {
    const method = "OPTIONS",
        { credentials = "same-origin" } = init;

    return fetch(url, { method, credentials, ...init }).then(checkResponse);
}

type HeadInit = RequestInit & InitWithAutoEncodedParameters & { method?: "HEAD" };
export function head(url: string, init: HeadInit = {}): Promise<Response> {
    const method = "HEAD";
    const { credentials = "same-origin", params } = init;

    let url_with_params = url;
    if (params && !isEmptyObject(params)) {
        url_with_params += encodeAllParamsToURI(params);
    }

    return fetch(url_with_params, { method, credentials, ...init }).then(checkResponse);
}

function checkResponse(response: Response): Response {
    if (response.ok) {
        return response;
    }
    throw new FetchWrapperError(response.statusText, response);
}

export class FetchWrapperError extends Error {
    readonly response: Response;

    constructor(message: string, response: Response) {
        super(message);
        this.response = response;
    }
}
