/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { credentialsAreEmpty, serverUrlIsValid, formatUrl } from "./gitlab-credentials-helper";

describe("Gitlab Credentials Helper", () => {
    describe("credentialsAreNotEmpty", () => {
        it("testReturnFalseWhenNoTokenKey", () => {
            const credentials = { server_url: "https://example.com" };

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenNoServerUrlKey", () => {
            const credentials = { token: "azerty1234" };

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenTokenIsEmpty", () => {
            const credentials = { token: "", server_url: "https://example.com" };

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenServerUrlIsEmpty", () => {
            const credentials = { token: "azerty1234", server_url: "" };

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnTrueWhenCredentialsAreValid", () => {
            const credentials = { token: "azerty1234", server_url: "https://example.com" };

            expect(credentialsAreEmpty(credentials)).toBeFalsy();
        });
    });
    describe("serverUrlIsValid", () => {
        it("testThrowErrorWhenServerUrlIsInvalid", () => {
            const credentials = { token: "azerty1234", server_url: "hts:/examplecom" };
            expect(serverUrlIsValid(credentials.server_url)).toBeFalsy();
        });

        it("testDontThrowErrorWhenCredentialsAreValid", () => {
            const credentials = { token: "azerty1234", server_url: "https://example.com" };
            expect(serverUrlIsValid(credentials.server_url)).toBeTruthy();
        });
    });
    describe("formatUrl", () => {
        it("When URL finishes with /, Then url is good", () => {
            const url = "https://example.com/";

            expect(formatUrl(url)).toEqual(
                "https://example.com/api/v4/projects?membership=true&per_page=20&min_access_level=40"
            );
        });

        it("When URL doesn't finish with /, Then url is good", () => {
            const url = "https://example.com";

            expect(formatUrl(url)).toEqual(
                "https://example.com/api/v4/projects?membership=true&per_page=20&min_access_level=40"
            );
        });
    });
});
