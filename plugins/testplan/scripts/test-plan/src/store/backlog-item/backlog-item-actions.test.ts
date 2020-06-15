/*
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { BacklogItemState } from "./type";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import * as tlp from "tlp";
import { loadBacklogItems } from "./backlog-item-actions";
import { BacklogItem } from "../../type";

jest.mock("tlp");

describe("BacklogItem state actions", () => {
    let context: ActionContext<BacklogItemState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
            } as RootState,
        } as unknown) as ActionContext<BacklogItemState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
    });

    describe("loadBacklogItems", () => {
        it("Retrieves all backlog items for milestone", async () => {
            tlpRecursiveGetMock.mockImplementation((route, config) => {
                config.getCollectionCallback([{ id: 123 }, { id: 124 }] as BacklogItem[]);
            });

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
            expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/milestones/42/content`, {
                params: { limit: 100 },
                getCollectionCallback: expect.any(Function),
            });
            expect(context.commit).toHaveBeenCalledWith("addBacklogItems", [
                { id: 123, is_expanded: false },
                { id: 124, is_expanded: false },
            ] as BacklogItem[]);
        });

        it("Catches error", async () => {
            const error = new Error();
            tlpRecursiveGetMock.mockRejectedValue(error);

            await expect(loadBacklogItems(context)).rejects.toThrow();

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).toHaveBeenCalledWith("loadingErrorHasBeenCatched");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
        });

        it("Does not catch 403 so that empty state can be displayed instead of error state", async () => {
            const error = { response: { status: 403 } };
            tlpRecursiveGetMock.mockRejectedValue(error);

            await loadBacklogItems(context);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingBacklogItems");
            expect(context.commit).not.toHaveBeenCalledWith("loadingErrorHasBeenCatched");
            expect(context.commit).toHaveBeenCalledWith("endLoadingBacklogItems");
        });
    });
});
