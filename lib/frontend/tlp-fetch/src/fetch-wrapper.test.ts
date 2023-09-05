/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import * as wrapper from "./fetch-wrapper";
import { mockFetchSuccess } from "../mocks/tlp-fetch-mock-helper";

describe(`fetch-wrapper`, () => {
    let globalFetch: SpyInstance;
    beforeEach(() => {
        window.fetch = globalFetch = vi.fn();
    });

    afterEach(() => {
        window.fetch = (): Promise<Response> => {
            throw new Error("Not supposed to happen");
        };
    });

    describe(`recursiveGet()`, () => {
        const url = "https://example.com/fetch-wrapper-recursive-get";

        function mockFetchSuccessForRecursiveGet(return_json: unknown, total: string): void {
            mockFetchSuccess(globalFetch, {
                return_json,
                headers: {
                    get(name): string | null {
                        if (name !== "X-PAGINATION-SIZE") {
                            return null;
                        }
                        return total;
                    },
                },
            });
        }

        it(`should query the given route URL with GET at least once
            and return a resolved Promise with the JSON return by the route`, async () => {
            const expected_result = [{ id: "terrifyingly" }, { id: "mannite" }];
            mockFetchSuccessForRecursiveGet(expected_result, "50");

            const result = await wrapper.recursiveGet(url, {
                params: {
                    limit: 50,
                    offset: 0,
                },
            });

            expect(result).toStrictEqual(expected_result);
            const expected_url =
                "https://example.com/fetch-wrapper-recursive-get?limit=50&offset=0";
            expect(window.fetch).toHaveBeenCalledWith(
                expected_url,
                expect.objectContaining({
                    method: "GET",
                    credentials: "same-origin",
                }),
            );
        });

        it(`given Fetch parameters like "cache" or "redirect", it will pass them to Fetch`, async () => {
            mockFetchSuccessForRecursiveGet({}, "0");

            await wrapper.recursiveGet(url, { cache: "force-cache", redirect: "error" });

            expect(window.fetch).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining({ cache: "force-cache", redirect: "error" }),
            );
        });

        it(`given parameters, it will URI-encode them and
            concatenate them to the given URL`, async () => {
            const params = {
                quinonyl: "mem",
                "R&D": 91,
                Jwahar: false,
            };
            mockFetchSuccessForRecursiveGet({}, "0");

            await wrapper.recursiveGet(url, { params });

            const expected_url =
                "https://example.com/fetch-wrapper-recursive-get?quinonyl=mem&R%26D=91&Jwahar=false&limit=100&offset=0";
            expect(window.fetch).toHaveBeenCalledWith(expected_url, expect.any(Object));
        });

        it(`should default limit to 100 and offset to 0`, async () => {
            mockFetchSuccessForRecursiveGet({}, "0");

            await wrapper.recursiveGet(url);

            const expected_url =
                "https://example.com/fetch-wrapper-recursive-get?limit=100&offset=0";
            expect(window.fetch).toHaveBeenCalledWith(expected_url, expect.any(Object));
        });

        it(`given a getCollectionCallback, it uses it to let the caller
            deal with getting to the "array of things"
            and calls it after the first GET request`, async () => {
            const options = {
                getCollectionCallback: vi.fn().mockImplementation(({ collection }) => collection),
            };
            const expected_collection = [{ id: 93 }, { id: 53 }];
            const return_json = {
                collection: expected_collection,
            };
            mockFetchSuccessForRecursiveGet(return_json, "0");

            const result = await wrapper.recursiveGet(url, options);

            expect(options.getCollectionCallback).toHaveBeenCalledWith(return_json);
            expect(result).toStrictEqual(expected_collection);
        });

        it(`When the route does not return a X-PAGINATION-SIZE header,
            it will throw`, async () => {
            mockFetchSuccess(globalFetch, { headers: { get: (): null => null } });

            await expect(wrapper.recursiveGet(url)).rejects.toThrow(
                "No X-PAGINATION-SIZE field in the header.",
            );
        });

        describe(`when the route provides a X-PAGINATION-SIZE header
            and there are more entries to fetch`, () => {
            function mockSuccessiveCalls(return_json: unknown): void {
                globalFetch.mockImplementationOnce(() => {
                    return {
                        headers: {
                            get(name: string): string | null {
                                if (name !== "X-PAGINATION-SIZE") {
                                    return null;
                                }
                                return "6";
                            },
                        },
                        ok: true,
                        json: (): Promise<unknown> => Promise.resolve(return_json),
                    };
                });
            }

            it(`will query all the remaining batches in parallel
                and will concatenate all entries into an array
                in the right order`, async () => {
                const batch_A = [{ id: 11 }, { id: 12 }];
                const batch_B = [{ id: 26 }, { id: 27 }];
                const batch_C = [{ id: 28 }, { id: 40 }];
                mockSuccessiveCalls(batch_A);
                mockSuccessiveCalls(batch_B);
                mockSuccessiveCalls(batch_C);

                const results = await wrapper.recursiveGet(url, { params: { limit: 2 } });

                const expected_results_in_order = batch_A.concat(batch_B).concat(batch_C);
                expect(results).toStrictEqual(expected_results_in_order);

                expect(globalFetch.mock.calls).toHaveLength(3);
                const [, ...later_calls] = globalFetch.mock.calls;
                later_calls.forEach((call) => {
                    const [, second_parameter] = call;
                    expect(second_parameter.params.limit).toBe(2);
                    expect([2, 4]).toContain(second_parameter.params.offset);
                });
            });

            it(`will call getCollectionCallback for each batch`, async () => {
                interface ObjectWithID {
                    id: number;
                }
                interface Collection {
                    collection: [ObjectWithID];
                }
                const first_batch = { collection: [{ id: 11 }, { id: 25 }] };
                const second_batch = { collection: [{ id: 26 }, { id: 27 }] };
                const third_batch = { collection: [{ id: 28 }, { id: 40 }] };
                mockSuccessiveCalls(first_batch);
                mockSuccessiveCalls(second_batch);
                mockSuccessiveCalls(third_batch);

                const results = await wrapper.recursiveGet<Collection, ObjectWithID>(url, {
                    params: { limit: 2 },
                    getCollectionCallback: ({ collection }) => collection,
                });

                expect(results).toStrictEqual([
                    { id: 11 },
                    { id: 25 },
                    { id: 26 },
                    { id: 27 },
                    { id: 28 },
                    { id: 40 },
                ]);
            });
        });

        it(`rejects call when no request can be sent`, async () => {
            await expect(wrapper.recursiveGet(url, {}, 0)).rejects.toThrowError(
                /At least one request/,
            );
        });
    });

    describe.each([
        ["get", wrapper.get],
        ["head", wrapper.head],
    ])(`%s can receive auto-encoded params`, (method_name, methodUnderTest) => {
        const url = "https://example.com/fetch-wrapper-test";
        it(`given "params", it will URI-encode them and concatenate them to the given URL`, async () => {
            const params = {
                quinonyl: "mem",
                "R&D": 91,
                Jwahar: false,
            };
            mockFetchSuccess(globalFetch);

            await methodUnderTest(url, { params });
            const expected_url =
                "https://example.com/fetch-wrapper-test?quinonyl=mem&R%26D=91&Jwahar=false";
            expect(window.fetch).toHaveBeenCalledWith(expected_url, expect.any(Object));
        });

        it(`given an empty params object {}, it will ignore it`, async () => {
            mockFetchSuccess(globalFetch);

            await methodUnderTest(url, { params: {} });
            expect(window.fetch).toHaveBeenCalledWith(
                "https://example.com/fetch-wrapper-test",
                expect.any(Object),
            );
        });
    });

    it.each([
        ["GET", wrapper.get],
        ["PUT", wrapper.put],
        ["PATCH", wrapper.patch],
        ["POST", wrapper.post],
        ["DELETE", wrapper.del],
    ])(
        `will query the given URL with %s and return Fetch's response`,
        async (expectedMethod, methodUnderTest) => {
            const url = "https://example.com/fetch-wrapper-test";
            mockFetchSuccess(globalFetch, { return_json: { value: "success" } });
            const response = await methodUnderTest(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: expectedMethod,
                    credentials: "same-origin",
                }),
            );
            const json = await response.json();
            expect(json.value).toBe("success");
        },
    );

    it.each([[wrapper.put], [wrapper.patch], [wrapper.post]])(
        `given a "body" and a "content-type" header, it will pass them to Fetch`,
        async (methodUnderTest) => {
            const url = "https://example.com/fetch-wrapper-test";
            mockFetchSuccess(globalFetch);

            const expected_options = {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({ hoodwise: "peripheroneural" }),
            };
            await methodUnderTest(url, expected_options);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining(expected_options),
            );
        },
    );

    it.each([
        [wrapper.options, "OPTIONS"],
        [wrapper.head, "HEAD"],
    ])(
        `will query the given URL with the appropriate method and return Fetch's response with headers`,
        async (methodUnderTest, expectedMethod) => {
            const url = "https://example.com/fetch-wrapper-test";

            mockFetchSuccess(globalFetch, {
                headers: {
                    get(name): string | null {
                        if (name !== "X-PAGINATION-SIZE") {
                            return null;
                        }
                        return "2";
                    },
                },
            });
            const result = await methodUnderTest(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: expectedMethod,
                    credentials: "same-origin",
                }),
            );
            expect(result.headers.get("X-PAGINATION-SIZE")).toBe("2");
        },
    );

    it.each([
        [wrapper.get],
        [wrapper.put],
        [wrapper.patch],
        [wrapper.post],
        [wrapper.del],
        [wrapper.options],
        [wrapper.head],
    ])(
        `given Fetch parameters like "cache" or "redirect", it will pass them to Fetch`,
        async (methodUnderTest) => {
            const url = "https://example.com/fetch-wrapper-test";
            mockFetchSuccess(globalFetch);

            await methodUnderTest(url, { cache: "force-cache", redirect: "error" });

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({ cache: "force-cache", redirect: "error" }),
            );
        },
    );

    it.each([
        [wrapper.get],
        [wrapper.recursiveGet],
        [wrapper.put],
        [wrapper.patch],
        [wrapper.post],
        [wrapper.del],
        [wrapper.options],
        [wrapper.head],
    ])(
        `when the route fails, it will return a rejected promise with the error`,
        async (methodUnderTest) => {
            const url = "https://example.com/fetch-wrapper-test";
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            globalFetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            Object.assign(expected_error, { response: expected_response });
            await expect(methodUnderTest(url)).rejects.toEqual(expected_error);
        },
    );
});
