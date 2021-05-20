/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { ERROR_TYPE_NO_GIT, ERROR_TYPE_UNKNOWN_ERROR } from "../constants";
import { getErrorCode } from "./error-handler";

describe("error-handler", () => {
    it("When the server responds with a 404, then the error for 'No git service' will be committed", async () => {
        const error_json = {
            response: {
                json(): Promise<{ error: { code: number; message: string } }> {
                    return Promise.resolve({
                        error: {
                            code: 404,
                            message: "Error",
                        },
                    });
                },
            },
        } as unknown as XMLHttpRequest;
        expect(await getErrorCode(error_json)).toBe(ERROR_TYPE_NO_GIT);
    });

    it("When the server responds with another error code, then the unknown error will be committed", async () => {
        const error_json = {
            response: {
                json(): Promise<{ error: { code: number; message: string } }> {
                    return Promise.resolve({
                        error: {
                            code: 403,
                            message: "Error",
                        },
                    });
                },
            },
        } as unknown as XMLHttpRequest;
        expect(await getErrorCode(error_json)).toBe(ERROR_TYPE_UNKNOWN_ERROR);
    });

    it("When something else happens (no response), then the unknown error will be committed", async () => {
        const error_json = {
            response: {
                json(): Promise<{ error: { message: string } }> {
                    return Promise.resolve({
                        error: {
                            message: "Error",
                        },
                    });
                },
            },
        } as unknown as XMLHttpRequest;
        expect(await getErrorCode(error_json)).toBe(ERROR_TYPE_UNKNOWN_ERROR);
    });
});
