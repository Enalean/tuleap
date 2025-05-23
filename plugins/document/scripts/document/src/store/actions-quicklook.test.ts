/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { TYPE_FILE } from "../constants";
import * as rest_querier from "../api/rest-querier";
import { toggleQuickLook } from "./actions-quicklook";
import type { Item, RootState } from "../type";
import type { ActionContext } from "vuex";

describe("actions-quicklook", () => {
    describe("toggleQuickLook", () => {
        let context: ActionContext<RootState, RootState>;

        beforeEach(() => {
            context = {
                commit: vi.fn(),
                state: {
                    folder_content: [{ id: 100, type: TYPE_FILE }],
                },
            } as unknown as ActionContext<RootState, RootState>;
        });

        it("should load item and store it as open in quick look", async () => {
            const item = {
                id: 123,
                title: "My file",
                type: TYPE_FILE,
                description: "n",
                owner: {
                    id: 102,
                },
                status: "none",
                obsolescence_date: null,
            } as Item;

            vi.spyOn(rest_querier, "getItem").mockResolvedValue(item);

            await toggleQuickLook(context, item.id);

            expect(context.commit).toHaveBeenCalledWith("beginLoadingCurrentlyPreviewedItem");
            expect(context.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", item);
            expect(context.commit).toHaveBeenCalledWith("toggleQuickLook", true);
            expect(context.commit).toHaveBeenCalledWith("stopLoadingCurrentlyPreviewedItem");
        });
    });
});
