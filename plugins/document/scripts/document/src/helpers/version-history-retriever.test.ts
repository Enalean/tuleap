/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as version_rest_querier from "../api/version-rest-querier";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_FOLDER, TYPE_LINK } from "../constants";
import type { FileHistory, Item } from "../type";
import { getVersionHistory } from "./version-history-retriever";

describe("getVersionHistory", () => {
    it("retrieve the version history if the item given is a file", async () => {
        const version_querier = jest.spyOn(version_rest_querier, "getFileVersionHistory");
        version_querier.mockResolvedValue([
            {
                id: 3,
                name: "Version 2",
                filename: "CocoLasticot.lol",
                download_href: "https://example.test/Coco",
            } as FileHistory,
        ]);

        const item = { id: 25, type: TYPE_FILE } as Item;

        const history = await getVersionHistory(item);

        expect(version_rest_querier.getFileVersionHistory).toHaveBeenCalledWith(25);
        expect(history).toStrictEqual([
            {
                id: 3,
                name: "Version 2",
                filename: "CocoLasticot.lol",
                download_href: "https://example.test/Coco",
            },
        ]);
    });

    it.each([
        [{ id: 25, type: TYPE_FOLDER } as Item],
        [{ id: 25, type: TYPE_LINK } as Item],
        [{ id: 25, type: TYPE_EMPTY } as Item],
        [{ id: 25, type: TYPE_EMBEDDED } as Item],
        [{ id: 25, type: "move" } as Item],
    ])("throw an error when the item is not a file", async (item: Item) => {
        const version_querier = jest.spyOn(version_rest_querier, "getFileVersionHistory");
        version_querier.mockResolvedValue([
            {
                id: 3,
                name: "Version 2",
                filename: "CocoLasticot.lol",
                download_href: "https://example.test/Coco",
            } as FileHistory,
        ]);
        await expect(getVersionHistory(item)).rejects.toThrow(
            "Item type's history not implemented",
        );
    });
});
