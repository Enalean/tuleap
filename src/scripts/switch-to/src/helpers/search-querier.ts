/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import type { Result } from "neverthrow";
import { okAsync, ResultAsync } from "neverthrow";
import type { ItemDefinition } from "../type";
import type { Fault } from "@tuleap/fault";
import type { EncodedURI } from "@tuleap/fetch-result";
import { JSONParseFault, post } from "@tuleap/fetch-result";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import type { FullTextState } from "../stores/type";
import type { StoppableQuery } from "./delayed-querier";

interface Pagination {
    readonly total: number;
}

export interface QueryResults {
    readonly results: FullTextState["fulltext_search_results"];
    readonly has_more_results: boolean;
    readonly next_offset: number;
}

export function querier(
    url: EncodedURI,
    keywords: string,
    previously_fetched_results: QueryResults,
    onItemReceived: (result: ItemDefinition) => void,
    onComplete: (result: ResultAsync<QueryResults, Fault>) => void,
): StoppableQuery {
    const PAGE_SIZE = 15;
    const MAX_PARALLEL_REQUESTS = 4;
    const limit = 50;

    const deduplicated_results: FullTextState["fulltext_search_results"] =
        previously_fetched_results.results;
    const previous_size = Object.keys(previously_fetched_results.results).length;
    let next_offset = previously_fetched_results.next_offset;

    let stop_pending_requests = false;

    function query(): ResultAsync<QueryResults, Fault> {
        if (stop_pending_requests) {
            return okAsync(previously_fetched_results);
        }

        return getFistPage()
            .andThen(getNextPages)
            .map((): QueryResults => {
                const keys = Object.keys(deduplicated_results);
                if (keys.length - previous_size <= PAGE_SIZE) {
                    return {
                        results: deduplicated_results,
                        has_more_results: false,
                        next_offset: next_offset,
                    };
                }

                const keys_to_keep = keys.slice(0, previous_size + PAGE_SIZE);
                return {
                    results: extractObject(deduplicated_results, keys_to_keep),
                    has_more_results: true,
                    next_offset: next_offset,
                };
            });

        function getFistPage(): ResultAsync<Pagination, Fault> {
            return searchAt(previously_fetched_results.next_offset).andThen(
                insertItemsAndGetPaginationFromFirstPage,
            );
        }

        function insertItemsAndGetPaginationFromFirstPage(
            response: Response,
        ): ResultAsync<Pagination, Fault> {
            const pagination_size = response.headers.get("X-PAGINATION-SIZE");
            if (pagination_size === null) {
                // This is likely an unexpected dev problem, we should not handle this case with Fault
                throw new Error("No X-PAGINATION-SIZE field in the header.");
            }
            const total = Number.parseInt(pagination_size, 10);

            return ResultAsync.fromPromise<void, Fault>(
                response
                    .json()
                    .then(insertItemsInDeduplicatedResults(previously_fetched_results.next_offset)),
                JSONParseFault.fromError,
            ).map(() => ({ total }));
        }

        function getNextPages(pagination: Pagination): ResultAsync<Result<void, Fault>[], Fault> {
            return ResultAsync.fromSafePromise(startParallelRequests(pagination));
        }

        function startParallelRequests({ total }: Pagination): Promise<Result<void, Fault>[]> {
            return limitConcurrencyPool(
                MAX_PARALLEL_REQUESTS,
                [...getAdditionalOffsets(previously_fetched_results.next_offset, limit, total)],
                getPageIfNecessary,
            );
        }

        function getPageIfNecessary(offset: number): ResultAsync<void, Fault> {
            if (doWeHaveEnoughResults()) {
                return ResultAsync.fromSafePromise(Promise.resolve());
            }

            return getPage(offset);
        }

        function getPage(offset: number): ResultAsync<void, Fault> {
            return searchAt(offset).andThen((response): ResultAsync<void, Fault> => {
                return ResultAsync.fromPromise<void, Fault>(
                    response.json().then(insertItemsInDeduplicatedResults(offset)),
                    JSONParseFault.fromError,
                );
            });
        }

        function insertItemsInDeduplicatedResults(
            current_offset: number,
        ): (json: ItemDefinition[]) => void {
            return (json: ItemDefinition[]): void => {
                if (doWeHaveEnoughResults()) {
                    return;
                }

                let nb_items_encountered = 0;
                for (const item of json) {
                    nb_items_encountered++;

                    if (typeof deduplicated_results[item.html_url] === "undefined") {
                        deduplicated_results[item.html_url] = item;
                        onItemReceived(item);
                    }

                    if (doWeHaveEnoughResults()) {
                        break;
                    }
                }

                next_offset = current_offset + nb_items_encountered;
            };
        }

        function doWeHaveEnoughResults(): boolean {
            return (
                stop_pending_requests ||
                Object.keys(deduplicated_results).length - previous_size >= PAGE_SIZE + 1
            );
        }

        function searchAt(offset: number): ResultAsync<Response, Fault> {
            return post(
                url,
                {
                    params: {
                        limit,
                        offset,
                    },
                },
                {
                    search_query: {
                        keywords,
                    },
                },
            );
        }
    }

    return {
        run: (): void => onComplete(query()),
        stop: (): void => {
            stop_pending_requests = true;
        },
    };
}

function extractObject<T>(obj: Record<string, T>, keys_to_keep: string[]): Record<string, T> {
    return Object.entries(obj).reduce((new_object, value) => {
        if (keys_to_keep.indexOf(value[0]) !== -1) {
            return Object.assign(new_object, {
                [value[0]]: value[1],
            });
        }
        return new_object;
    }, {});
}

function* getAdditionalOffsets(offset: number, limit: number, total: number): Generator<number> {
    let new_offset = offset;
    while (new_offset + limit < total) {
        new_offset += limit;
        yield new_offset;
    }
    return new_offset;
}
