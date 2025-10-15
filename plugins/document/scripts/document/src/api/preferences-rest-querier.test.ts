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

import { describe, expect, it, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";

import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants";
import {
    deleteUserPreferencesForFolderInProject,
    getPreferenceForEmbeddedDisplay,
    patchUserPreferencesForFolderInProject,
    removeUserPreferenceForEmbeddedDisplay,
    setNarrowModeForEmbeddedDisplay,
} from "./preferences-rest-querier";
import { EMBEDDED_FILE_DISPLAY_LARGE, EMBEDDED_FILE_DISPLAY_NARROW } from "../type";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { uri } from "@tuleap/fetch-result";

describe("User preferences", () => {
    const user_id = 102;
    const project_id = 110;
    const folder_id = 30;
    const preference_key = "plugin_docman_hide_110_30";

    describe("patchUserPreferencesForFolderInProject() -", () => {
        it("should set the current user's preferencies for a given folder on 'expanded'", async () => {
            const patchResponse = vi.spyOn(fetch_result, "patchResponse");
            patchResponse.mockReturnValue(okAsync({} as Response));
            const result = await patchUserPreferencesForFolderInProject(
                user_id,
                project_id,
                folder_id,
            );

            expect(result.isOk()).toBe(true);
            expect(patchResponse).toHaveBeenCalledWith(
                uri`/api/users/102/preferences`,
                {},
                {
                    key: preference_key,
                    value: DOCMAN_FOLDER_EXPANDED_VALUE,
                },
            );
        });
    });

    describe("deleteUserPreferencesForFolderInProject() -", () => {
        it("should delete the current user's preferencies for a given folder (e.g collapsed)", async () => {
            const del = vi.spyOn(fetch_result, "del");
            del.mockReturnValue(okAsync({} as Response));
            const result = await deleteUserPreferencesForFolderInProject(
                user_id,
                project_id,
                folder_id,
            );

            expect(result.isOk()).toBe(true);
            expect(del).toHaveBeenCalledWith(
                uri`/api/users/102/preferences?key=plugin_docman_hide_110_30`,
            );
        });
    });

    describe("getPreferenceForEmbeddedDisplay() -", () => {
        it.each([
            ["narrow", EMBEDDED_FILE_DISPLAY_NARROW],
            [false, EMBEDDED_FILE_DISPLAY_LARGE],
        ])("when api give '%s', it should return '%s'", async (preference, expected) => {
            const getJSON = vi.spyOn(fetch_result, "getJSON");

            getJSON.mockReturnValue(okAsync({ key: "", value: preference }));
            const result = await getPreferenceForEmbeddedDisplay(user_id, project_id, folder_id);

            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(null)).toStrictEqual(expected);
        });

        it("should return a fault when api failed", async () => {
            const getJSON = vi.spyOn(fetch_result, "getJSON");

            getJSON.mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
            const result = await getPreferenceForEmbeddedDisplay(user_id, project_id, folder_id);

            expect(result.isErr()).toBe(true);
        });
    });

    describe("setNarrowModeForEmbeddedDisplay() -", () => {
        it("should call patchResponse then return 'narrow'", async () => {
            const patchResponse = vi.spyOn(fetch_result, "patchResponse");

            patchResponse.mockReturnValue(okAsync({} as Response));
            const result = await setNarrowModeForEmbeddedDisplay(102, 101, 54);

            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(null)).toStrictEqual(EMBEDDED_FILE_DISPLAY_NARROW);
        });

        it("should call patchResponse then return its fault", async () => {
            const patchResponse = vi.spyOn(fetch_result, "patchResponse");

            patchResponse.mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
            const result = await setNarrowModeForEmbeddedDisplay(102, 101, 54);

            expect(result.isErr()).toBe(true);
        });
    });

    describe("removeUserPreferenceForEmbeddedDisplay() -", () => {
        it("should call del then return 'narrow'", async () => {
            const del = vi.spyOn(fetch_result, "del");

            del.mockReturnValue(okAsync({} as Response));
            const result = await removeUserPreferenceForEmbeddedDisplay(102, 101, 54);

            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(null)).toStrictEqual(EMBEDDED_FILE_DISPLAY_LARGE);
        });

        it("should call del then return its fault", async () => {
            const del = vi.spyOn(fetch_result, "del");

            del.mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
            const result = await removeUserPreferenceForEmbeddedDisplay(102, 101, 54);

            expect(result.isErr()).toBe(true);
        });
    });
});
