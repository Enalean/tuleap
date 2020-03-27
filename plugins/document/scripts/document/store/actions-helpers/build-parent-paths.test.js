/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { buildItemPath } from "./build-parent-paths.js";

describe("buildItemPath", () => {
    it("Build item parent path", () => {
        const item = {
            item_id: 10,
            item_name: "my item",
        };

        const parents = [
            {
                item_id: 1,
                title: "folder A",
            },
            {
                item_id: 2,
                title: "folder B",
            },
            {
                item_id: 3,
                title: "folder C",
            },
            {
                item_id: 4,
                title: "folder D",
            },
        ];

        const item_path = buildItemPath(item, parents);
        expect(item_path).toEqual({
            path: "/folder A/folder B/folder C/folder D/my item",
            id: 10,
        });
    });
});
