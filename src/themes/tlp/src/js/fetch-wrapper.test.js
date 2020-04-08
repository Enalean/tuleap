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

import * as wrapper from "./fetch-wrapper";

function mockFetchSuccess(return_json, headers = {}) {
    window.fetch.mockImplementation(() =>
        Promise.resolve({
            headers,
            ok: true,
            json: () => Promise.resolve(return_json),
        })
    );
}

describe(`fetch-wrapper`, () => {
    beforeEach(() => {
        // eslint-disable-next-line jest/prefer-spy-on
        window.fetch = jest.fn();
    });

    afterEach(() => {
        delete window.fetch;
    });

    describe(`get()`, () => {
        const url = "https://example.com/fetch-wrapper-get";

        it(`will query the given URL with GET`, async () => {
            mockFetchSuccess({ value: 1 });

            const response = await wrapper.get(url);
            const json = await response.json();
            expect(json.value).toBe(1);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "GET",
                    credentials: "same-origin",
                })
            );
        });

        it(`given parameters, it will URI-encode them and
            concatenate them to the given URL`, async () => {
            const params = {
                quinonyl: "mem",
                "R&D": 91,
                Jwahar: false,
            };
            mockFetchSuccess();

            await wrapper.get(url, { params });

            const expected_url =
                "https://example.com/fetch-wrapper-get?quinonyl=mem&R%26D=91&Jwahar=false";
            expect(window.fetch).toHaveBeenCalledWith(expected_url, expect.any(Object));
        });

        it(`given Fetch parameters like "cache" or "redirect", it will pass them to Fetch`, async () => {
            const options = {
                cache: "force-cache",
                redirect: "error",
            };
            mockFetchSuccess();

            await wrapper.get(url, options);

            expect(window.fetch).toHaveBeenCalledWith(url, expect.objectContaining(options));
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.get(url)).rejects.toEqual(expected_error);
        });
    });

    describe(`recursiveGet()`, () => {
        const url = "https://example.com/fetch-wrapper-recursive-get";

        function mockFetchSuccessForRecursiveGet(return_json, total) {
            mockFetchSuccess(return_json, {
                get(name) {
                    if (name !== "X-PAGINATION-SIZE") {
                        return null;
                    }
                    return total;
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

            expect(result).toEqual(expected_result);
            const expected_url =
                "https://example.com/fetch-wrapper-recursive-get?limit=50&offset=0";
            expect(window.fetch).toHaveBeenCalledWith(
                expected_url,
                expect.objectContaining({
                    method: "GET",
                    credentials: "same-origin",
                })
            );
        });

        it(`given Fetch parameters like "cache" or "redirect", it will pass them to Fetch`, async () => {
            const options = {
                cache: "force-cache",
                redirect: "error",
            };
            mockFetchSuccessForRecursiveGet({}, "0");

            await wrapper.recursiveGet(url, options);

            expect(window.fetch).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining(options)
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
                getCollectionCallback: jest.fn().mockImplementation(({ collection }) => collection),
            };
            const expected_collection = [{ id: 93 }, { id: 53 }];
            const return_json = {
                collection: expected_collection,
            };
            mockFetchSuccessForRecursiveGet(return_json, "0");

            const result = await wrapper.recursiveGet(url, options);

            expect(options.getCollectionCallback).toHaveBeenCalledWith(return_json);
            expect(result).toEqual(expected_collection);
        });

        it(`When the route does not return a X-PAGINATION-SIZE header,
            it will throw`, async () => {
            mockFetchSuccess(
                {},
                {
                    get: () => null,
                }
            );

            await expect(wrapper.recursiveGet(url)).rejects.toThrow(
                "No X-PAGINATION-SIZE field in the header."
            );
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.recursiveGet(url)).rejects.toEqual(expected_error);
        });

        describe(`when the route provides a X-PAGINATION-SIZE header
            and there are more entries to fetch`, () => {
            function mockSuccessiveCalls(return_json) {
                window.fetch.mockImplementationOnce(() => {
                    return {
                        headers: {
                            get(name) {
                                if (name !== "X-PAGINATION-SIZE") {
                                    return null;
                                }
                                return "6";
                            },
                        },
                        ok: true,
                        json: () => Promise.resolve(return_json),
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
                expect(results).toEqual(expected_results_in_order);

                expect(window.fetch.mock.calls.length).toBe(3);
                const [, ...later_calls] = window.fetch.mock.calls;
                later_calls.forEach((call) => {
                    const [, second_parameter] = call;
                    expect(second_parameter.params.limit).toEqual(2);
                    expect([2, 4]).toContain(second_parameter.params.offset);
                });
            });

            it(`will call getCollectionCallback for each batch`, async () => {
                const first_batch = { collection: [{ id: 11 }, { id: 25 }] };
                const second_batch = { collection: [{ id: 26 }, { id: 27 }] };
                const third_batch = { collection: [{ id: 28 }, { id: 40 }] };
                mockSuccessiveCalls(first_batch);
                mockSuccessiveCalls(second_batch);
                mockSuccessiveCalls(third_batch);

                const results = await wrapper.recursiveGet(url, {
                    params: { limit: 2 },
                    getCollectionCallback: ({ collection }) => collection,
                });

                expect(results).toEqual([
                    { id: 11 },
                    { id: 25 },
                    { id: 26 },
                    { id: 27 },
                    { id: 28 },
                    { id: 40 },
                ]);
            });
        });
    });

    describe(`put()`, () => {
        const url = "https://example.com/fetch-wrapper-put";

        it(`will query the given URL with PUT and return Fetch's response`, async () => {
            mockFetchSuccess("success");
            const result = await wrapper.put(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "PUT",
                    credentials: "same-origin",
                })
            );
            expect(await result.json()).toEqual("success");
        });

        it(`given a "body" and a "content-type" header, it will pass them to Fetch`, async () => {
            mockFetchSuccess();

            const expected_options = {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({ hoodwise: "peripheroneural" }),
            };
            await wrapper.put(url, expected_options);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining(expected_options)
            );
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.put(url)).rejects.toEqual(expected_error);
        });
    });

    describe(`patch()`, () => {
        const url = "https://example.com/fetch-wrapper-patch";

        it(`will query the given URL with PATCH and return Fetch's response`, async () => {
            mockFetchSuccess("success");
            const result = await wrapper.patch(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "PATCH",
                    credentials: "same-origin",
                })
            );
            expect(await result.json()).toEqual("success");
        });

        it(`given a "body" and a "content-type" header, it will pass them to Fetch`, async () => {
            mockFetchSuccess();

            const expected_options = {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({ hoodwise: "peripheroneural" }),
            };
            await wrapper.patch(url, expected_options);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining(expected_options)
            );
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.patch(url)).rejects.toEqual(expected_error);
        });
    });

    describe(`post()`, () => {
        const url = "https://example.com/fetch-wrapper-post";

        it(`will query the given URL with POST and return Fetch's response`, async () => {
            mockFetchSuccess("success");
            const result = await wrapper.post(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "POST",
                    credentials: "same-origin",
                })
            );
            expect(await result.json()).toEqual("success");
        });

        it(`given a "body" and a "content-type" header, it will pass them to Fetch`, async () => {
            mockFetchSuccess();

            const expected_options = {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({ hoodwise: "peripheroneural" }),
            };
            await wrapper.post(url, expected_options);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining(expected_options)
            );
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.post(url)).rejects.toEqual(expected_error);
        });
    });

    describe(`del()`, () => {
        const url = "https://example.com/fetch-wrapper-del";

        it(`will query the given URL with DELETE and return Fetch's response`, async () => {
            mockFetchSuccess("success");
            const result = await wrapper.del(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "DELETE",
                    credentials: "same-origin",
                })
            );
            expect(await result.json()).toEqual("success");
        });

        it(`given Fetch parameters like "redirect", it will pass them to Fetch`, async () => {
            const options = { redirect: "error" };
            mockFetchSuccess();

            await wrapper.del(url, options);

            expect(window.fetch).toHaveBeenCalledWith(url, expect.objectContaining(options));
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.del(url)).rejects.toEqual(expected_error);
        });
    });

    describe(`options()`, () => {
        const url = "https://example.com/fetch-wrapper-options";

        it(`will query the given URL with OPTIONS and return Fetch's response`, async () => {
            mockFetchSuccess("success", {
                get(name) {
                    if (name !== "X-PAGINATION-SIZE") {
                        return null;
                    }
                    return "2";
                },
            });
            const result = await wrapper.options(url);

            expect(window.fetch).toHaveBeenCalledWith(
                url,
                expect.objectContaining({
                    method: "OPTIONS",
                    credentials: "same-origin",
                })
            );
            expect(await result.headers.get("X-PAGINATION-SIZE")).toEqual("2");
        });

        it(`given Fetch parameters like "redirect", it will pass them to Fetch`, async () => {
            const options = { redirect: "error" };
            mockFetchSuccess();

            await wrapper.options(url, options);

            expect(window.fetch).toHaveBeenCalledWith(url, expect.objectContaining(options));
        });

        it(`when the route fails, it will return a rejected promise with the error`, async () => {
            const expected_response = {
                ok: false,
                statusText: "Not found",
            };
            window.fetch.mockImplementation(() => Promise.resolve(expected_response));

            const expected_error = new Error("Not found");
            expected_error.response = expected_response;
            await expect(wrapper.options(url)).rejects.toEqual(expected_error);
        });
    });
});
