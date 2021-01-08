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
import * as tlp from "tlp";

jest.mock("tlp");

describe("Gitlab Api Querier", () => {
    it("When api is called, Then the request with correct headers is sent", async () => {
        const credentials = {
            server_url: "https://example.com",
            token: "azerty1234",
        };

        const headers = new Headers();
        headers.append("Authorization", "Bearer " + credentials.token);

        await getAsyncGitlabRepositoryList(credentials);

        expect(tlp.get).toHaveBeenCalledWith("https://example.com", {
            cache: "default",
            headers,
            mode: "cors",
        });
    });
});
