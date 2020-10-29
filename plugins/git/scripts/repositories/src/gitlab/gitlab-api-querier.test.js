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

import { getAsyncGitlabRepositoryList } from "./gitlab-api-querier";

describe("Gitlab Api Querier", () => {
    afterEach(() => {
        // eslint-disable-next-line no-undef
        global.fetch.mockClear();
    });
    it("When api is called, Then repositories are recovered", async () => {
        const credentials = {
            server_url: "https://example.com",
            token: "azerty1234",
        };

        // eslint-disable-next-line jest/prefer-spy-on, no-undef
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve([{ id: 1 }]),
            })
        );

        const response = await getAsyncGitlabRepositoryList(credentials);
        expect(await response.json()).toEqual([{ id: 1 }]);

        const headers = new Headers();
        headers.append("Authorization", "Bearer " + credentials.token);

        // eslint-disable-next-line no-undef
        expect(global.fetch).toHaveBeenCalledWith("https://example.com", {
            cache: "default",
            headers,
            method: "GET",
            mode: "cors",
        });
    });
});
