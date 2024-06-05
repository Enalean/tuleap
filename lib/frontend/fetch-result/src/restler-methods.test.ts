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

import { beforeEach, describe, expect, it } from "vitest";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { ResponseRetriever } from "./ResponseRetriever";
import type { PatchMethod, PostMethod, PutMethod } from "./constants";
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
import { uri as uriTag } from "./uri-string-template";
import {
    buildDelete,
    buildGetJSON,
    buildGetResponse,
    buildHead,
    buildOptions,
    buildSendAndReceiveJSON,
    buildSendJSON,
} from "./restler-methods";
import type { OptionsWithAutoEncodedParameters } from "./options";

type JSONResponsePayload = {
    readonly id: number;
    readonly value: string;
};
type JSONRequestPayload = {
    readonly request_id: number;
    readonly request_value: string;
};
type Parameters = {
    readonly [key: string]: string | number | boolean;
};
type ResponseResult = ResultAsync<Response, Fault>;
type JSONResult = ResultAsync<JSONResponsePayload, Fault>;

const isTuleapAPIFault = (fault: Fault): boolean =>
    "isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true;

const ID = 521;
const REQUEST_ID = 196;

describe(`restler-methods`, () => {
    let success_response: Response,
        fetcher: FetchInterfaceStub,
        json_response_payload: JSONResponsePayload,
        json_request_payload: JSONRequestPayload,
        params: Parameters;
    const uri = uriTag`https://example.com/result-fetcher-test/${"démo"}`;

    beforeEach(() => {
        json_response_payload = { id: ID, value: "headmaster" };
        json_request_payload = { request_id: REQUEST_ID, request_value: "Sphindus" };
        params = { quinonyl: "mem", "R&D": 91, Jwahar: false };
        success_response = { ok: true } as Response;
        fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response);
    });

    function* provideHeadAndGetResponse(): Generator<[string, () => ResponseResult]> {
        yield [
            HEAD_METHOD,
            (): ResponseResult => buildHead(ResponseRetriever(fetcher))(uri, { params }),
        ];
        yield [
            GET_METHOD,
            (): ResponseResult => buildGetResponse(ResponseRetriever(fetcher))(uri, { params }),
        ];
    }

    it.each([...provideHeadAndGetResponse()])(
        `it will encode the given URI in addition to the given parameters
            and set the %s method
            and will return a ResultAsync with the Response`,
        async (expected_http_method, method_under_test) => {
            const result = await method_under_test();
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(result.value).toBe(success_response);
            expect(fetcher.getRequestInfo(0)).toBe(
                "https://example.com/result-fetcher-test/d%C3%A9mo?quinonyl=mem&R%26D=91&Jwahar=false",
            );

            const request_init = fetcher.getRequestInit(0);
            if (request_init === undefined) {
                throw Error("Expected request init to be defined");
            }
            expect(request_init.method).toBe(expected_http_method);
            expect(request_init.credentials).toBe("same-origin");
        },
    );

    it(`head() options are not mandatory`, async () => {
        await buildHead(ResponseRetriever(fetcher))(uri);

        expect(fetcher.getRequestInfo(0)).toBe("https://example.com/result-fetcher-test/d%C3%A9mo");
    });

    it(`get() options are not mandatory`, async () => {
        await buildGetResponse(ResponseRetriever(fetcher))(uri);

        expect(fetcher.getRequestInfo(0)).toBe("https://example.com/result-fetcher-test/d%C3%A9mo");
    });

    function* provideOptionsAndDelete(): Generator<[string, () => ResponseResult]> {
        yield [OPTIONS_METHOD, (): ResponseResult => buildOptions(ResponseRetriever(fetcher))(uri)];
        yield [DELETE_METHOD, (): ResponseResult => buildDelete(ResponseRetriever(fetcher))(uri)];
    }

    it.each([...provideOptionsAndDelete()])(
        `it will encode the given URI
        and set the %s method
        and will return a ResultAsync with the Response`,
        async (expected_http_method, method_under_test) => {
            const result = await method_under_test();
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(result.value).toBe(success_response);
            expect(fetcher.getRequestInfo(0)).toBe(
                "https://example.com/result-fetcher-test/d%C3%A9mo",
            );
            const request_init = fetcher.getRequestInit(0);
            if (request_init === undefined) {
                throw new Error("Expected request init to be defined");
            }
            expect(request_init.method).toBe(expected_http_method);
            expect(request_init.credentials).toBe("same-origin");
        },
    );

    function* provideMethodsSendingJSON(): Generator<[PutMethod | PatchMethod | PostMethod]> {
        yield [PATCH_METHOD];
        yield [POST_METHOD];
        yield [PUT_METHOD];
    }

    it.each([...provideMethodsSendingJSON()])(
        `sendJSON will encode the given URI in addition to the given parameters
            and set the %s method
            and add the JSON Content-Type header
            and stringify the given JSON payload
            and will return a ResultAsync with the Response`,
        async (expected_http_method) => {
            const result = await buildSendJSON(ResponseRetriever(fetcher), expected_http_method)(
                uri,
                { params },
                json_request_payload,
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(result.value).toBe(success_response);
            expect(fetcher.getRequestInfo(0)).toBe(
                "https://example.com/result-fetcher-test/d%C3%A9mo?quinonyl=mem&R%26D=91&Jwahar=false",
            );

            const request_init = fetcher.getRequestInit(0);
            if (request_init === undefined) {
                throw Error("Expected request init to be defined");
            }
            expect(request_init.method).toBe(expected_http_method);
            expect(request_init.credentials).toBe("same-origin");

            if (!(request_init.headers instanceof Headers)) {
                throw Error("Expected headers to be set");
            }
            expect(request_init.headers.get("Content-Type")).toBe("application/json");
            expect(request_init.body).toBe(`{"request_id":196,"request_value":"Sphindus"}`);
        },
    );

    describe(`JSON-reading methods`, () => {
        beforeEach(() => {
            const success_response_with_payload = {
                ok: true,
                json: () => Promise.resolve(json_response_payload),
            } as Response;
            fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response_with_payload);
        });

        describe(`getJSON()`, () => {
            const callGetJSON = <TypeOfJSONPayload>(
                uri: EncodedURI,
                options?: OptionsWithAutoEncodedParameters,
            ): ResultAsync<TypeOfJSONPayload, Fault> =>
                buildGetJSON(ResponseRetriever(fetcher))(uri, options);

            it(`will encode the given URI with the given parameters
                and will return a ResultAsync with the decoded JSON from the Response body`, async () => {
                const result = await callGetJSON<JSONResponsePayload>(uri, { params });
                if (!result.isOk()) {
                    throw new Error("Expected an Ok");
                }

                expect(result.value).toBe(json_response_payload);
                expect(result.value.id).toBe(ID);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo?quinonyl=mem&R%26D=91&Jwahar=false",
                );

                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw new Error("Expected request init to be defined");
                }
                expect(request_init.method).toBe(GET_METHOD);
                expect(request_init.credentials).toBe("same-origin");
            });

            it(`options are not mandatory`, async () => {
                await callGetJSON(uri);

                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo",
                );
            });
        });

        it.each([...provideMethodsSendingJSON()])(
            `sendAndReceiveJSON will encode the given URI
            and set the %s method
            and add the JSON Content-Type header
            and stringify the given JSON payload
            and will return a ResultAsync with the decoded JSON from the Response body`,
            async (expected_http_method) => {
                const result = await buildSendAndReceiveJSON(
                    ResponseRetriever(fetcher),
                    expected_http_method,
                )<JSONResponsePayload>(uri, json_request_payload);
                if (!result.isOk()) {
                    throw Error("Expected an Ok");
                }

                expect(result.value).toBe(json_response_payload);
                expect(result.value.id).toBe(ID);
                expect(fetcher.getRequestInfo(0)).toBe(
                    "https://example.com/result-fetcher-test/d%C3%A9mo",
                );

                const request_init = fetcher.getRequestInit(0);
                if (request_init === undefined) {
                    throw Error("Expected request init to be defined");
                }

                expect(request_init.method).toBe(expected_http_method);
                expect(request_init.credentials).toBe("same-origin");

                if (!(request_init.headers instanceof Headers)) {
                    throw new Error("Expected headers to be set");
                }
                expect(request_init.headers.get("Content-Type")).toBe("application/json");
                expect(request_init.body).toBe(`{"request_id":196,"request_value":"Sphindus"}`);
            },
        );
    });

    function* provideMethodsReturningFaults(): Generator<[() => ResultAsync<unknown, Fault>]> {
        yield [(): JSONResult => buildGetJSON(ResponseRetriever(fetcher))(uri)];
        yield [(): ResponseResult => buildHead(ResponseRetriever(fetcher))(uri, {})];
        yield [(): ResponseResult => buildGetResponse(ResponseRetriever(fetcher))(uri, {})];
        yield [(): ResponseResult => buildOptions(ResponseRetriever(fetcher))(uri)];
        yield [(): ResponseResult => buildDelete(ResponseRetriever(fetcher))(uri)];
        yield [
            (): ResponseResult =>
                buildSendJSON(ResponseRetriever(fetcher), POST_METHOD)(
                    uri,
                    {},
                    json_request_payload,
                ),
        ];
        yield [
            (): JSONResult =>
                buildSendAndReceiveJSON(ResponseRetriever(fetcher), PATCH_METHOD)(
                    uri,
                    json_request_payload,
                ),
        ];
    }

    it.each([...provideMethodsReturningFaults()])(
        `when there is an API error, it will read the Restler error format
        and will return an Err with a TuleapAPIFault`,
        async (method_under_test) => {
            const api_error_response = {
                ok: false,
                status: 400,
                json: () => Promise.resolve({ error: { message: "moderantist cakehouse" } }),
            } as Response;
            fetcher = FetchInterfaceStub.withSuccessiveResponses(api_error_response);

            const result = await method_under_test();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isTuleapAPIFault(result.error)).toBe(true);
        },
    );
});
