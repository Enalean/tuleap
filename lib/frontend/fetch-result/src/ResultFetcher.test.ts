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

import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { FetchResult } from "./ResultFetcher";
import { ResultFetcher } from "./ResultFetcher";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { ResponseRetriever } from "./ResponseRetriever";
import { AllGetter } from "./AllGetter";
import {
    DELETE_METHOD,
    GET_METHOD,
    HEAD_METHOD,
    OPTIONS_METHOD,
    PATCH_METHOD,
    POST_METHOD,
    PUT_METHOD,
} from "./constants";

type ResponseResult = ResultAsync<Response, Fault>;
type JSONPayload = {
    readonly id: number;
    readonly value: string;
};
type Parameters = {
    readonly [key: string]: string | number | boolean;
};

const ID = 521;

describe(`ResultFetcher`, () => {
    let success_response: Response,
        fetcher: FetchInterfaceStub,
        json_payload: JSONPayload,
        params: Parameters;
    const uri = "https://example.com/result-fetcher-test/démo";

    beforeEach(() => {
        success_response = { ok: true } as unknown as Response;
        fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response);
        json_payload = { id: ID, value: "headmaster" };
        params = {
            quinonyl: "mem",
            "R&D": 91,
            Jwahar: false,
        };
    });

    const getFetcher = (): FetchResult => {
        const response_retriever = ResponseRetriever(fetcher);
        const all_getter = AllGetter(response_retriever);
        return ResultFetcher(response_retriever, all_getter);
    };

    describe(`methods returning a JSON payload`, () => {
        beforeEach(() => {
            const success_response_with_payload = {
                ok: true,
                json: () => Promise.resolve(json_payload),
            } as unknown as Response;
            fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response_with_payload);
        });

        describe(`getJSON()`, () => {
            it(`will encode the given URI with the given parameters
                and will return a ResultAsync with the decoded JSON from the Response body`, async () => {
                const result = await getFetcher().getJSON<JSONPayload>(uri, { params });
                if (!result.isOk()) {
                    throw new Error("Expected an Ok");
                }

                expect(result.value).toBe(json_payload);
                expect(result.value.id).toBe(ID);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo?quinonyl=mem&R%26D=91&Jwahar=false"
                );

                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw new Error("Expected request init to be defined");
                }
                expect(request_init.method).toBe(GET_METHOD);
                expect(request_init.credentials).toBe("same-origin");
            });

            it(`options are not mandatory`, async () => {
                await getFetcher().getJSON(uri);

                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo"
                );
            });
        });
    });

    describe(`methods returning a Response`, () => {
        describe(`head()`, () => {
            it(`will encode the given URI with the given parameters
                and will return a ResultAsync with the Response`, async () => {
                const result = await getFetcher().head(uri, { params });
                if (!result.isOk()) {
                    throw new Error("Expected an Ok");
                }

                expect(result.value).toBe(success_response);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo?quinonyl=mem&R%26D=91&Jwahar=false"
                );

                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw new Error("Expected request init to be defined");
                }
                expect(request_init.method).toBe(HEAD_METHOD);
                expect(request_init.credentials).toBe("same-origin");
            });

            it(`options are not mandatory`, async () => {
                await getFetcher().head(uri);

                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo"
                );
            });
        });

        it.each([
            [
                "putJSON()",
                (): ResponseResult => getFetcher().putJSON(uri, json_payload),
                PUT_METHOD,
            ],
            [
                "patchJSON()",
                (): ResponseResult => getFetcher().patchJSON(uri, json_payload),
                PATCH_METHOD,
            ],
            [
                "postJSON()",
                (): ResponseResult => getFetcher().postJSON(uri, json_payload),
                POST_METHOD,
            ],
        ])(
            `%s will encode the given URI and stringify the given JSON payload
            and add the JSON Content-Type header and will return a ResultAsync with the Response`,
            async (
                _method_name: string,
                method_under_test: () => ResponseResult,
                expected_http_method: string
            ) => {
                const result = await method_under_test();
                if (!result.isOk()) {
                    throw new Error("Expected an Ok");
                }

                expect(result.value).toBe(success_response);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo"
                );
                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw new Error("Expected request init to be defined");
                }
                expect(request_init.method).toBe(expected_http_method);
                expect(request_init.credentials).toBe("same-origin");

                if (!(request_init.headers instanceof Headers)) {
                    throw new Error("Expected headers to be set");
                }
                expect(request_init.headers.get("Content-Type")).toBe("application/json");
                expect(request_init.body).toBe(`{"id":521,"value":"headmaster"}`);
            }
        );

        it.each([
            ["options()", (): ResponseResult => getFetcher().options(uri), OPTIONS_METHOD],
            ["del()", (): ResponseResult => getFetcher().del(uri), DELETE_METHOD],
        ])(
            `%s will encode the given URI and will return a ResultAsync with the Response`,
            async (
                _method_name: string,
                method_under_test: () => ResponseResult,
                expected_http_method: string
            ) => {
                const result = await method_under_test();
                if (!result.isOk()) {
                    throw new Error("Expected an Ok");
                }

                expect(result.value).toBe(success_response);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo"
                );
                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw new Error("Expected request init to be defined");
                }
                expect(request_init.method).toBe(expected_http_method);
                expect(request_init.credentials).toBe("same-origin");
            }
        );
    });
});
