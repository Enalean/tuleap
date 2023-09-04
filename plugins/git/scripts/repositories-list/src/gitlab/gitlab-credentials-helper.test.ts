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

import {
    credentialsAreEmpty,
    serverUrlIsValid,
    formatUrlToGetAllProject,
    formatUrlToGetProjectFromId,
} from "./gitlab-credentials-helper";
import type { GitLabCredentials } from "../type";

describe("Gitlab Credentials Helper", () => {
    describe("credentialsAreNotEmpty", () => {
        it("testReturnFalseWhenNoTokenKey", () => {
            const credentials = { server_url: "https://example.com" } as GitLabCredentials;

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenNoServerUrlKey", () => {
            const credentials = { token: "azerty1234" } as GitLabCredentials;

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenTokenIsEmpty", () => {
            const credentials = {
                token: "",
                server_url: "https://example.com",
            } as GitLabCredentials;

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnFalseWhenServerUrlIsEmpty", () => {
            const credentials = { token: "azerty1234", server_url: "" } as GitLabCredentials;

            expect(credentialsAreEmpty(credentials)).toBeTruthy();
        });

        it("testReturnTrueWhenCredentialsAreValid", () => {
            const credentials = {
                token: "azerty1234",
                server_url: "https://example.com",
            } as GitLabCredentials;

            expect(credentialsAreEmpty(credentials)).toBeFalsy();
        });
    });
    describe("serverUrlIsValid", () => {
        it("testThrowErrorWhenServerUrlIsInvalid", () => {
            const credentials = {
                token: "azerty1234",
                server_url: "hts:/examplecom",
            } as GitLabCredentials;
            expect(serverUrlIsValid(credentials.server_url)).toBeFalsy();
        });

        it("testDontThrowErrorWhenCredentialsAreValid", () => {
            const credentials = {
                token: "azerty1234",
                server_url: "https://example.com",
            } as GitLabCredentials;
            expect(serverUrlIsValid(credentials.server_url)).toBeTruthy();
        });
    });
    describe("formatUrlToGetAllProject", () => {
        it("When URL finishes with /, Then url is good", () => {
            const url = "https://example.com/";

            expect(formatUrlToGetAllProject(url)).toBe(
                "https://example.com/api/v4/projects?membership=true&per_page=20&min_access_level=40",
            );
        });

        it("When URL doesn't finish with /, Then url is good", () => {
            const url = "https://example.com";

            expect(formatUrlToGetAllProject(url)).toBe(
                "https://example.com/api/v4/projects?membership=true&per_page=20&min_access_level=40",
            );
        });
    });
    describe("formatUrlToGetProjectFromId", () => {
        it("When URL finishes with /, Then url is good", () => {
            const url = "https://example.com/";

            expect(formatUrlToGetProjectFromId(url, 1)).toBe(
                "https://example.com/api/v4/projects/1",
            );
        });

        it("When URL doesn't finish with /, Then url is good", () => {
            const url = "https://example.com";

            expect(formatUrlToGetProjectFromId(url, 1)).toBe(
                "https://example.com/api/v4/projects/1",
            );
        });
    });
});
