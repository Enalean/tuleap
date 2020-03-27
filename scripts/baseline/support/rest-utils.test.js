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

describe("RestUtils:", () => {
    describe("getErrorMessage()", () => {
        const exception = {
            response: {},
        };
        describe("with no error in exception", () => {
            let result;

            beforeEach(async () => {
                exception.response.json = () => Promise.resolve({});
                result = await getMessageFromException(exception);
            });

            it("returns nothing", () => expect(result).toEqual(null));
        });

        describe("with non internationalized exception", () => {
            let result;

            beforeEach(async () => {
                exception.response.json = () =>
                    Promise.resolve({
                        error: { message: "non internationalized" },
                    });
                result = await getMessageFromException(exception);
            });

            it("returns a non internationalized message", () =>
                expect(result).toEqual("non internationalized"));
        });

        describe("with internationalized exception", () => {
            let result;

            beforeEach(async () => {
                exception.response.json = () =>
                    Promise.resolve({
                        error: {
                            i18n_error_message: "internationalized message",
                        },
                    });
                result = await getMessageFromException(exception);
            });

            it("returns an internationalized message", () =>
                expect(result).toEqual("internationalized message"));
        });
    });
});
