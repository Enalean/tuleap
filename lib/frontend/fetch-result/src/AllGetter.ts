/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { Result, ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import type { AutoEncodedParameters } from "./auto-encoder";
import { getURI } from "./auto-encoder";
import { JSONParseFault } from "./faults/JSONParseFault";
import type { RetrieveResponse } from "./ResponseRetriever";
import { GET_METHOD } from "./constants";
import type { EncodedURI } from "./uri-string-template";
import { credentials } from "./headers";

type GetAllLimitParameters = {
    readonly limit?: number;
    readonly offset?: number;
};

export type GetAllCollectionCallback<TypeOfArrayItem, TypeOfJSONPayload> = (
    json: TypeOfJSONPayload,
) => ReadonlyArray<TypeOfArrayItem>;

export type GetAllOptions<TypeOfArrayItem, TypeOfJSONPayload> = {
    readonly params?: AutoEncodedParameters & GetAllLimitParameters;
    getCollectionCallback?: GetAllCollectionCallback<TypeOfArrayItem, TypeOfJSONPayload>;
    readonly max_parallel_requests?: number;
};

function defaultGetCollectionCallback<TypeOfJSONPayload>(
    json: TypeOfJSONPayload,
): ReadonlyArray<TypeOfJSONPayload> {
    if (json instanceof Array) {
        return json;
    }
    return [json];
}

export const PAGINATION_SIZE_HEADER = "X-PAGINATION-SIZE";

type ArrayOfResultOfArrayItems<TypeOfArrayItem> = ReadonlyArray<
    Result<ReadonlyArray<TypeOfArrayItem>, Fault>
>;

const flatten = <TypeOfArrayItem>(
    all_responses_result: ResultAsync<ArrayOfResultOfArrayItems<TypeOfArrayItem>, Fault>,
    first_results: ReadonlyArray<TypeOfArrayItem>,
): ResultAsync<ReadonlyArray<TypeOfArrayItem>, Fault> =>
    all_responses_result
        // flatten the Result[] into a single Result
        .andThen((results) => Result.combine(results))
        .map((nested_array) =>
            // flatten the ArrayItem[][] into ArrayItem[] and concat after first_results
            nested_array.reduce(
                (accumulator, array_items) => accumulator.concat(array_items),
                first_results,
            ),
        );

export type GetAll = {
    getAllJSON<TypeOfArrayItem, TypeOfJSONPayload = ReadonlyArray<TypeOfArrayItem>>(
        uri: EncodedURI,
        options?: GetAllOptions<TypeOfArrayItem, TypeOfJSONPayload>,
    ): ResultAsync<ReadonlyArray<TypeOfArrayItem>, Fault>;
};

export const AllGetter = (response_retriever: RetrieveResponse): GetAll => {
    function getAllJSON<TypeOfArrayItem, TypeOfJSONPayload>(
        uri: EncodedURI,
        options: GetAllOptions<TypeOfArrayItem, TypeOfJSONPayload> = {},
    ): ResultAsync<ReadonlyArray<TypeOfArrayItem>, Fault> {
        const { params = {}, max_parallel_requests = 6 } = options;
        if (max_parallel_requests < 1) {
            // This is a dev problem, we use an error intentionally
            throw new Error("At least one request needs to be sent to retrieve data");
        }
        const getCollectionCallback = options.getCollectionCallback ?? defaultGetCollectionCallback;
        const { limit = 100, offset = 0 } = params;

        const first_call_params = { ...params, limit, offset };
        const first_call = response_retriever
            .retrieveResponse(getURI(uri, first_call_params), { method: GET_METHOD, credentials })
            .andThen((response) => {
                const pagination_size = response.headers.get(PAGINATION_SIZE_HEADER);
                if (pagination_size === null) {
                    // This is likely an unexpected dev problem, we should not handle this case with Fault
                    throw new Error("No X-PAGINATION-SIZE field in the header.");
                }
                const size = Number.parseInt(pagination_size, 10);

                return ResultAsync.fromPromise<ReadonlyArray<TypeOfArrayItem>, Fault>(
                    // Abuse response.json() returning Promise<any> to "cheat" the types.
                    // We assume response.json() will return some type compatible with TypeOfArrayItem
                    response.json().then((json) => getCollectionCallback(json)),
                    JSONParseFault.fromError,
                ).map((items) => ({ value: items, size }));
            });

        const final_result = first_call.andThen((result_with_size) => {
            const first_results = result_with_size.value;
            const total = result_with_size.size;

            // SafePromise because each request is already a ResultAsync and always resolves
            const all_responses_result = ResultAsync.fromSafePromise<
                ArrayOfResultOfArrayItems<TypeOfArrayItem>,
                Fault
            >(
                limitConcurrencyPool(
                    max_parallel_requests,
                    [...getAdditionalOffsets(offset, limit, total)],
                    (new_offset) => {
                        const new_params = { ...params, limit, offset: new_offset };
                        return response_retriever
                            .retrieveResponse(getURI(uri, new_params), {
                                method: GET_METHOD,
                                credentials,
                            })
                            .andThen((response) => {
                                // Abuse response.json() returning Promise<any> to "cheat" the types.
                                // We assume response.json() will return some type compatible with TypeOfArrayItem
                                return ResultAsync.fromPromise<
                                    ReadonlyArray<TypeOfArrayItem>,
                                    Fault
                                >(
                                    response.json().then((json) => getCollectionCallback(json)),
                                    JSONParseFault.fromError,
                                );
                            });
                    },
                ),
            );

            return flatten(all_responses_result, first_results);
        });
        return final_result;
    }

    return { getAllJSON };
};

function* getAdditionalOffsets(offset: number, limit: number, total: number): Generator<number> {
    let new_offset = offset;
    while (new_offset + limit < total) {
        new_offset += limit;
        yield new_offset;
    }
    return new_offset;
}
