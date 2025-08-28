/*
 *  Copyright (c) Enalean, $today.year-Present. All Rights Reserved.
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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import * as rest_querier from "../../api/preferencies-rest-querier";
import {
    displayEmbeddedInLargeMode,
    displayEmbeddedInNarrowMode,
    setUserPreferenciesForFolder,
} from "./preferencies-actions";
import type { UserPreferenciesFolderSetPayload } from "./preferencies-actions";
import type { Embedded, RootState } from "../../type";
import type { PreferenciesState } from "./preferencies-default-state";
import type { ActionContext } from "vuex";

describe("setUserPreferenciesForFolder", () => {
    let patchUserPreferenciesForFolderInProject: MockInstance;
    let deleteUserPreferenciesForFolderInProject: MockInstance;

    beforeEach(() => {
        patchUserPreferenciesForFolderInProject = vi
            .spyOn(rest_querier, "patchUserPreferenciesForFolderInProject")
            .mockReturnValue(Promise.resolve());
        deleteUserPreferenciesForFolderInProject = vi
            .spyOn(rest_querier, "deleteUserPreferenciesForFolderInProject")
            .mockReturnValue(Promise.resolve());
    });

    it("sets the user preference for the state of a given folder if its new state is 'open' (expanded)", async () => {
        const folder_id = 30;
        const should_be_closed = false;
        const context: ActionContext<PreferenciesState, RootState> = {} as ActionContext<
            PreferenciesState,
            RootState
        >;

        const payload: UserPreferenciesFolderSetPayload = {
            folder_id,
            should_be_closed,
            user_id: 102,
            project_id: 110,
        };

        await setUserPreferenciesForFolder(context, payload);

        expect(patchUserPreferenciesForFolderInProject).toHaveBeenCalled();
        expect(deleteUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
    });

    it("deletes the user preference for the state of a given folder if its new state is 'closed' (collapsed)", async () => {
        const folder_id = 30;
        const should_be_closed = true;
        const context: ActionContext<PreferenciesState, RootState> = {} as ActionContext<
            PreferenciesState,
            RootState
        >;

        const payload: UserPreferenciesFolderSetPayload = {
            folder_id,
            should_be_closed,
            user_id: 102,
            project_id: 110,
        };
        await setUserPreferenciesForFolder(context, payload);

        expect(patchUserPreferenciesForFolderInProject).not.toHaveBeenCalled();
        expect(deleteUserPreferenciesForFolderInProject).toHaveBeenCalled();
    });
});

describe("displayEmbeddedInLargeMode", () => {
    let context: ActionContext<PreferenciesState, RootState>;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
        } as unknown as ActionContext<PreferenciesState, RootState>;

        vi.spyOn(rest_querier, "removeUserPreferenceForEmbeddedDisplay").mockReturnValue(
            Promise.resolve(),
        );
    });

    it("should store in user preferences the new mode and then update the store value", async () => {
        const item = {
            id: 123,
            title: "My embedded",
        } as Embedded;

        await displayEmbeddedInLargeMode(context, { item, user_id: 254, project_id: 101 });

        expect(context.commit).toHaveBeenCalledWith("shouldDisplayEmbeddedInLargeMode", true);
    });
});

describe("displayEmbeddedInNarrowMode", () => {
    let context: ActionContext<PreferenciesState, RootState>;

    beforeEach(() => {
        context = {
            rootState: {
                configuration: { user_id: 102, project_id: 110 },
            },
            commit: vi.fn(),
        } as unknown as ActionContext<PreferenciesState, RootState>;

        vi.spyOn(rest_querier, "setNarrowModeForEmbeddedDisplay").mockReturnValue(
            Promise.resolve(),
        );
    });

    it("should store in user preferences the new mode and then update the store value", async () => {
        const item = {
            id: 123,
            title: "My embedded",
        } as Embedded;

        await displayEmbeddedInNarrowMode(context, { item, user_id: 254, project_id: 101 });

        expect(context.commit).toHaveBeenCalledWith("shouldDisplayEmbeddedInLargeMode", false);
    });
});
