/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, type MockInstance, vi } from "vitest";
import * as rest_querier from "../../api/preferences-rest-querier";
import type { RootState } from "../../type";
import { setUserPreferencesForFolder } from "./folder-preferences";
import type { ActionContext } from "vuex";
import { okAsync } from "neverthrow";

describe("folder-preferences", () => {
    describe("setUserPreferenciesForFolder", () => {
        let patchUserPreferenciesForFolderInProject: MockInstance;
        let deleteUserPreferenciesForFolderInProject: MockInstance;

        beforeEach(() => {
            patchUserPreferenciesForFolderInProject = vi
                .spyOn(rest_querier, "patchUserPreferencesForFolderInProject")
                .mockReturnValue(okAsync({} as Response));
            deleteUserPreferenciesForFolderInProject = vi
                .spyOn(rest_querier, "deleteUserPreferencesForFolderInProject")
                .mockReturnValue(okAsync({} as Response));
        });

        it("sets the user preference for the state of a given folder if its new state is 'open' (expanded)", async () => {
            const folder_id = 30;
            const should_be_closed = false;
            const context: ActionContext<RootState, RootState> = {} as ActionContext<
                RootState,
                RootState
            >;

            await setUserPreferencesForFolder(context, folder_id, should_be_closed, 102, 110);

            expect(patchUserPreferenciesForFolderInProject).toHaveBeenCalled();
            expect(deleteUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
        });

        it("deletes the user preference for the state of a given folder if its new state is 'closed' (collapsed)", async () => {
            const folder_id = 30;
            const should_be_closed = true;
            const context: ActionContext<RootState, RootState> = {} as ActionContext<
                RootState,
                RootState
            >;

            await setUserPreferencesForFolder(context, folder_id, should_be_closed, 102, 110);

            expect(patchUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
            expect(deleteUserPreferenciesForFolderInProject).toHaveBeenCalled();
        });
    });
});
