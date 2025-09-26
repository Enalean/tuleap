/*
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
import * as lock_rest_querier from "../../api/lock-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import { TYPE_EMBEDDED, TYPE_FILE } from "../../constants";
import { unlockDocument } from "./lock-actions";
import type { ActionContext } from "vuex";
import type { Embedded, ItemFile, State } from "../../type";

describe("unlock", () => {
    let getItem: MockInstance;
    let context: ActionContext<State, State>;

    beforeEach(() => {
        context = { commit: vi.fn(), dispatch: vi.fn() } as unknown as ActionContext<State, State>;

        vi.spyOn(lock_rest_querier, "deleteLockFile").mockResolvedValue();
        vi.spyOn(lock_rest_querier, "deleteLockEmbedded").mockResolvedValue();
        getItem = vi.spyOn(rest_querier, "getItem");
    });

    it("should unlock a file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
        } as ItemFile;

        const updated_item = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
            lock_info: {
                user_id: 123,
            },
        };
        getItem.mockReturnValue(Promise.resolve(updated_item));

        await unlockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceFolderContentByItem", updated_item, {
            root: true,
        });
    });

    it("should unlock an embedded file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_EMBEDDED,
        } as Embedded;

        const updated_item = {
            id: 123,
            title: "My embedded",
            type: TYPE_EMBEDDED,
            lock_info: {
                user_id: 123,
            },
        };
        getItem.mockReturnValue(Promise.resolve(updated_item));

        await unlockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceFolderContentByItem", updated_item, {
            root: true,
        });
    });
});
