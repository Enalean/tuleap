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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { GetAll } from "./AllGetter";
import { AllGetter, PAGINATION_SIZE_HEADER } from "./AllGetter";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { ResponseRetriever } from "./ResponseRetriever";
import { uri as uriTag } from "./uri-string-template";

function buildResponse<TypeOfJSONPayload>(payload: TypeOfJSONPayload, total: number): Response {
    return {
        ok: true,
        headers: {
            get: (name: string): string | null =>
                name === PAGINATION_SIZE_HEADER ? String(total) : null,
        },
        json: (): Promise<TypeOfJSONPayload> => Promise.resolve(payload),
    } as unknown as Response;
}

type ArrayItem = {
    readonly id: string;
};

describe(`AllGetter`, () => {
    let fetcher: FetchInterfaceStub, json_payload: ReadonlyArray<ArrayItem>;
    const uri = uriTag`https://example.com/all-getter-test`;

    const buildGetter = (): GetAll => AllGetter(ResponseRetriever(fetcher));

    beforeEach(() => {
        json_payload = [{ id: "terrifyingly" }, { id: "mannite" }];
        const response = buildResponse(json_payload, 50);
        fetcher = FetchInterfaceStub.withSuccessiveResponses(response);
    });

    it(`queries the given URI with GET at least once
        and will return a ResultAsync with an array of JSON items returned by the endpoint`, async () => {
        const result = await buildGetter().getAllJSON(uri, { params: { limit: 50, offset: 50 } });

        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }
        expect(result.value).toBe(json_payload);
        const init = fetcher.getRequestInit(0);
        if (!init) {
            throw new Error("Fetch should have received init parameters");
        }
        expect(init.method).toBe("GET");
        expect(init.credentials).toBe("same-origin");
        expect(fetcher.getRequestInfo(0)).toContain("limit=50");
        expect(fetcher.getRequestInfo(0)).toContain("offset=50");
    });

    it(`will throw when max parallel requests is ≤ 0`, () => {
        expect(() => buildGetter().getAllJSON(uri, { max_parallel_requests: 0 })).toThrowError(
            /At least one request/
        );
    });

    it(`given parameters, it will URI-encode them and concatenate them to the given URI`, async () => {
        const params = {
            quinonyl: "mem",
            "R&D": 91,
            Jwahar: false,
        };
        await buildGetter().getAllJSON(uri, { params });

        expect(fetcher.getRequestInfo(0)).toBe(
            "https://example.com/all-getter-test?quinonyl=mem&R%26D=91&Jwahar=false&limit=100&offset=0"
        );
    });

    it(`defaults limit to 100 and offset to 0`, async () => {
        await buildGetter().getAllJSON(uri);
        expect(fetcher.getRequestInfo(0)).toContain("limit=100");
        expect(fetcher.getRequestInfo(0)).toContain("offset=0");
    });

    it(`defaults to wrapping the JSON Payload in an array if it isn't one`, async () => {
        const json_payload = { id: 158 };
        const response = buildResponse(json_payload, 50);
        fetcher = FetchInterfaceStub.withSuccessiveResponses(response);

        const result = await buildGetter().getAllJSON(uri);
        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }
        expect(result.value).toStrictEqual([json_payload]);
    });

    it(`given a getCollectionCallback, it uses it to let the caller
        deal with getting to the "array of things"
        and calls it after the first GET request`, async () => {
        const options = {
            getCollectionCallback: vi.fn().mockImplementation(({ collection }) => collection),
        };
        const collection = [{ id: 93 }, { id: 53 }];
        const json_payload = { collection };
        const response = buildResponse(json_payload, 50);
        fetcher = FetchInterfaceStub.withSuccessiveResponses(response);

        const result = await buildGetter().getAllJSON(uri, options);

        expect(options.getCollectionCallback).toHaveBeenCalledWith(json_payload);
        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }
        expect(result.value).toBe(collection);
    });

    it(`when the Response does not have a X-PAGINATION-SIZE header, it will throw`, async () => {
        const response = { ok: true, headers: { get: (): null => null } } as unknown as Response;
        fetcher = FetchInterfaceStub.withSuccessiveResponses(response);

        await expect(buildGetter().getAllJSON(uri)).rejects.toThrow(
            "No X-PAGINATION-SIZE field in the header."
        );
    });

    describe(`when the route provides a X-PAGINATION-SIZE header
        and there are more entries to fetch`, () => {
        interface ObjectWithID {
            id: number;
        }

        let batch_A: ReadonlyArray<ObjectWithID>,
            batch_B: ReadonlyArray<ObjectWithID>,
            batch_C: ReadonlyArray<ObjectWithID>,
            expected_results_in_order: ReadonlyArray<ObjectWithID>;

        beforeEach(() => {
            batch_A = [{ id: 11 }, { id: 12 }];
            batch_B = [{ id: 26 }, { id: 27 }];
            batch_C = [{ id: 28 }, { id: 40 }];
            expected_results_in_order = batch_A.concat(batch_B).concat(batch_C);
        });

        const buildResponseWithTotalSix = <TypeOfJSONPayload>(
            payload: TypeOfJSONPayload
        ): Response => buildResponse(payload, 6);

        it(`will query all the remaining batches in parallel
            and will concatenate all entries into an array
            in the right order`, async () => {
            fetcher = FetchInterfaceStub.withSuccessiveResponses(
                buildResponseWithTotalSix(batch_A),
                buildResponseWithTotalSix(batch_B),
                buildResponseWithTotalSix(batch_C)
            );

            const result = await buildGetter().getAllJSON(uri, { params: { limit: 2 } });
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(result.value).toStrictEqual(expected_results_in_order);
            expect(fetcher.getRequestInfo(1)).toContain("limit=2");
            expect(fetcher.getRequestInfo(1)).toContain("offset=2");
            expect(fetcher.getRequestInit(1)?.credentials).toBe("same-origin");
            expect(fetcher.getRequestInfo(2)).toContain("limit=2");
            expect(fetcher.getRequestInfo(2)).toContain("offset=4");
            expect(fetcher.getRequestInit(2)?.credentials).toBe("same-origin");
        });

        it(`will call getCollectionCallback for each batch`, async () => {
            interface Collection {
                readonly collection: ObjectWithID[];
            }

            fetcher = FetchInterfaceStub.withSuccessiveResponses(
                buildResponseWithTotalSix({ collection: batch_A }),
                buildResponseWithTotalSix({ collection: batch_B }),
                buildResponseWithTotalSix({ collection: batch_C })
            );

            const result = await buildGetter().getAllJSON(uri, {
                params: { limit: 2 },
                getCollectionCallback: (payload: Collection) => payload.collection,
            });
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }

            expect(result.value).toStrictEqual(expected_results_in_order);
        });
    });
});
