/*
 *  Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as tlp_fetch from "@tuleap/tlp-fetch";

import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants";
import {
    deleteUserPreferenciesForFolderInProject,
    patchUserPreferenciesForFolderInProject,
} from "./preferencies-rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("User preferences", () => {
    const user_id = 102;
    const project_id = 110;
    const folder_id = 30;
    const preference_key = "plugin_docman_hide_110_30";
    const headers = {
        headers: {
            "Content-Type": "application/json",
        },
    };

    describe("patchUserPreferenciesForFolderInProject() -", () => {
        it("should set the current user's preferencies for a given folder on 'expanded'", async () => {
            const tlpPatch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(tlpPatch);
            await patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

            expect(tlpPatch).toHaveBeenCalledWith("/api/users/102/preferences", {
                ...headers,
                body: JSON.stringify({
                    key: preference_key,
                    value: DOCMAN_FOLDER_EXPANDED_VALUE,
                }),
            });
        });
    });

    describe("deleteUserPreferenciesForFolderInProject() -", () => {
        it("should delete the current user's preferencies for a given folder (e.g collapsed)", async () => {
            const tlpDel = jest.spyOn(tlp_fetch, "del");
            mockFetchSuccess(tlpDel);
            await deleteUserPreferenciesForFolderInProject(user_id, project_id, folder_id);

            expect(tlpDel).toHaveBeenCalledWith(
                "/api/users/102/preferences?key=plugin_docman_hide_110_30",
            );
        });
    });
});
