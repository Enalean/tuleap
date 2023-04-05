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

import { describe, it, expect } from "vitest";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { FetchInterfaceStub } from "../tests/stubs/FetchInterfaceStub";
import { ResponseRetriever } from "./ResponseRetriever";
import type { FetchInterface } from "./FetchInterface";
import type { SupportedHTTPMethod } from "./constants";
import {
    DELETE_METHOD,
    GET_METHOD,
    HEAD_METHOD,
    OPTIONS_METHOD,
    PATCH_METHOD,
    POST_METHOD,
    PUT_METHOD,
} from "./constants";
import { getEncodedURIString, uri as uriTag } from "./uri-string-template";

const isNetworkFault = (fault: Fault): boolean =>
    "isNetworkFault" in fault && fault.isNetworkFault() === true;

describe(`ResponseRetriever`, () => {
    let fetcher: FetchInterface;
    const success_response = { ok: true } as unknown as Response;
    const uri = uriTag`https://example.com/response-retriever-test`;

    const retrieve = (): ResultAsync<Response, Fault> => {
        const retriever = ResponseRetriever(fetcher);
        return retriever.retrieveResponse(uri, { method: "GET" });
    };

    it.each([
        [GET_METHOD],
        [HEAD_METHOD],
        [OPTIONS_METHOD],
        [POST_METHOD],
        [PUT_METHOD],
        [PATCH_METHOD],
        [DELETE_METHOD],
    ])(
        `will query the given URI with method %s and return a ResultAsync with the response`,
        async (method: SupportedHTTPMethod) => {
            const fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response);
            const retriever = ResponseRetriever(fetcher);

            const result = await retriever.retrieveResponse(uri, { method });
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }
            expect(result.value).toBe(success_response);
            expect(fetcher.getRequestInfo(0)).toBe(getEncodedURIString(uri));
            const request_init = fetcher.getRequestInit(0);
            if (!request_init) {
                throw new Error("Expected a request init");
            }
            expect(request_init.method).toBe(method);
        }
    );

    it(`will pass credentials, mode, headers and body along to fetch if given in options`, async () => {
        const fetcher = FetchInterfaceStub.withSuccessiveResponses(success_response);
        const retriever = ResponseRetriever(fetcher);

        const body = JSON.stringify({ key: "value" });
        const result = await retriever.retrieveResponse(uri, {
            method: "PATCH",
            credentials: "same-origin",
            mode: "same-origin",
            headers: new Headers({ "Content-Type": "application/json" }),
            body,
        });
        if (!result.isOk()) {
            throw new Error("Expected an OK");
        }
        expect(result.value).toBe(success_response);
        const request_init = fetcher.getRequestInit(0);
        if (!request_init) {
            throw new Error("Expected a request init");
        }
        expect(request_init.method).toBe("PATCH");
        expect(request_init.credentials).toBe("same-origin");
        expect(request_init.mode).toBe("same-origin");
        if (!(request_init.headers instanceof Headers)) {
            throw new Error("Expected headers");
        }
        expect(request_init.headers.has("Content-Type")).toBe(true);
        expect(request_init.headers.get("Content-Type")).toBe("application/json");
        expect(request_init.body).toBe(body);
    });

    it(`when there is a network error, it will return an Err with a NetworkFault`, async () => {
        fetcher = FetchInterfaceStub.withNetworkError(new Error("Internet disconnected"));
        const result = await retrieve();
        if (!result.isErr()) {
            throw new Error("Expected an Err");
        }
        expect(isNetworkFault(result.error)).toBe(true);
    });
});
