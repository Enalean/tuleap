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
 *
 */

import { getMessageFromException } from "./rest-utils";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("RestUtils:", () => {
    let result: string | null;
    function getFetchWrapperError(json: unknown): FetchWrapperError {
        const error: FetchWrapperError = {
            response: {
                json: () => Promise.resolve(json),
            },
        } as FetchWrapperError;

        return error;
    }

    describe("getErrorMessage()", () => {
        describe("with no error in exception", () => {
            beforeEach(async () => {
                result = await getMessageFromException(getFetchWrapperError({}));
            });

            it("returns nothing", () => expect(result).toBeNull());
        });

        describe("with non internationalized exception", () => {
            beforeEach(async () => {
                result = await getMessageFromException(
                    getFetchWrapperError({
                        error: { message: "non internationalized" },
                    }),
                );
            });

            it("returns a non internationalized message", () =>
                expect(result).toBe("non internationalized"));
        });

        describe("with internationalized exception", () => {
            beforeEach(async () => {
                result = await getMessageFromException(
                    getFetchWrapperError({
                        error: {
                            i18n_error_message: "internationalized message",
                        },
                    }),
                );
            });

            it("returns an internationalized message", () =>
                expect(result).toBe("internationalized message"));
        });
    });
});
