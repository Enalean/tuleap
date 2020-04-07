/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import * as tlp from "tlp";
import { mockFetchSuccess } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper";
import { Context, Credentials, State } from "./type";
import { getJiraProjectList } from "./actions";

jest.mock("tlp");

describe("getJiraProjectList", () => {
    let context: Context;

    it("retrieve the list of jira projects", async () => {
        const credentials = {} as Credentials;
        context = {
            commit: jest.fn(),
            state: {
                project_unix_name: "project",
            } as State,
        };

        const tlpPost = jest.spyOn(tlp, "post");

        mockFetchSuccess(tlpPost, credentials);

        await getJiraProjectList(context, credentials);
        expect(tlpPost).toHaveBeenCalledWith("/plugins/tracker/project/jira/project_list", {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ credentials }),
        });
    });
});
