/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import { GET_METHOD, POST_METHOD } from "./constants";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { buildGetTextResponse, buildSendFormAndReceiveText } from "./text-methods";
import { ResponseRetriever } from "./ResponseRetriever";
import { uri as uriTag } from "./uri-string-template";

type Parameters = {
    readonly [key: string]: string | number | boolean;
};
type ResponseResult = ResultAsync<Response, Fault>;
type TextResult = ResultAsync<string, Fault>;

const isTuleapAPIFault = (fault: Fault): boolean =>
    "isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true;

const ID = 398;

describe(`text-methods`, () => {
    let fetcher: FetchInterfaceStub,
        request_payload: FormData,
        success_response: Response,
        text_response: string,
        params: Parameters;

    const uri = uriTag`https://example.com/text-method-test/${"démo"}`;

    beforeEach(() => {
        params = { penciliform: "showboard", "J&C": 113, Pulveraceous: false };
        request_payload = new FormData();
        request_payload.set("id", String(ID));
        request_payload.set("value", "overstoping protephemeroid");
        text_response = "Pantotheria fizzer";
        success_response = {
            ok: true,
            text: () => Promise.resolve(text_response),
        } as Response;
        fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response);
    });

    it(`getTextResponse will encode the given URI in addition to the given parameters
        and set the GET method
        and will return a ResultAsync with the Response`, async () => {
        const getTextResponse = buildGetTextResponse(ResponseRetriever(fetcher));
        const result = await getTextResponse(uri, { params });
        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }

        expect(result.value).toBe(success_response);
        expect(fetcher.getRequestInfo(0)).toBe(
            "https://example.com/text-method-test/d%C3%A9mo?penciliform=showboard&J%26C=113&Pulveraceous=false",
        );

        const request_init = fetcher.getRequestInit(0);
        if (request_init === undefined) {
            throw Error("Expected request init to be defined");
        }
        expect(request_init.method).toBe(GET_METHOD);
        expect(request_init.credentials).toBe("same-origin");
    });

    function* provideMethodsSendingFormData(): Generator<[string, () => TextResult]> {
        yield [
            POST_METHOD,
            (): TextResult =>
                buildSendFormAndReceiveText(ResponseRetriever(fetcher), POST_METHOD)(
                    uri,
                    request_payload,
                ),
        ];
    }

    it.each([...provideMethodsSendingFormData()])(
        `it will encode the given URI
        and set the %s method
        and add the given FormData payload
        and will return a ResultAsync with the Response decoded as text`,
        async (expected_http_method, method_under_test) => {
            const result = await method_under_test();
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toBe(text_response);
            expect(fetcher.getRequestInfo(0)).toBe(
                "https://example.com/text-method-test/d%C3%A9mo",
            );

            const request_init = fetcher.getRequestInit(0);
            if (request_init === undefined) {
                throw Error("Expected request init to be defined");
            }
            expect(request_init.method).toBe(expected_http_method);
            expect(request_init.credentials).toBe("same-origin");

            if (!(request_init.body instanceof FormData)) {
                throw Error("Expected body to be set");
            }
            expect(request_init.body.get("id")).toBe(String(ID));
        },
    );

    function* provideMethodsReturningFaults(): Generator<[() => ResultAsync<unknown, Fault>]> {
        yield [(): ResponseResult => buildGetTextResponse(ResponseRetriever(fetcher))(uri)];
        yield [
            (): TextResult =>
                buildSendFormAndReceiveText(ResponseRetriever(fetcher), POST_METHOD)(
                    uri,
                    request_payload,
                ),
        ];
    }

    it.each([...provideMethodsReturningFaults()])(
        `when there is an API error, it will read the response as text
        and will return an Err with a TuleapAPIFault`,
        async (method_under_test) => {
            const api_error_response = {
                ok: false,
                text: () => Promise.resolve("caducean merlin"),
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
