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

import type { FetchInterface } from "../../src/FetchInterface";

export interface FetchInterfaceStub extends FetchInterface {
    getRequestInfo(call: number): RequestInfo | URL | undefined;
    getRequestInit(call: number): RequestInit | undefined;
}

export const FetchInterfaceStub = {
    withSuccessiveResponses: (
        first_response: Response,
        ...other_responses: Response[]
    ): FetchInterfaceStub => {
        const all_responses = [first_response, ...other_responses];
        const recorded_arguments = new Map<number, [RequestInfo | URL, RequestInit | undefined]>();
        let calls = 0;
        const fetchStub = (
            info: RequestInfo | URL,
            init: RequestInit | undefined,
        ): Promise<Response> => {
            recorded_arguments.set(calls, [info, init]);
            calls++;
            const response = all_responses.shift();
            if (response !== undefined) {
                return Promise.resolve(response);
            }
            throw new Error("No response configured");
        };
        return {
            // See https://github.com/nodejs/undici/issues/1943
            // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
            fetch: fetchStub as typeof fetch,
            getRequestInfo(call: number): RequestInfo | URL | undefined {
                const call_arguments = recorded_arguments.get(call);
                return call_arguments ? call_arguments[0] : undefined;
            },

            getRequestInit(call: number): RequestInit | undefined {
                const call_arguments = recorded_arguments.get(call);
                return call_arguments ? call_arguments[1] : undefined;
            },
        };
    },

    withNetworkError: (error: Error): FetchInterface => ({
        fetch(): Promise<never> {
            return Promise.reject(error);
        },
    }),
};
