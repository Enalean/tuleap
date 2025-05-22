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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, expect, it } from "vitest";
import { isItemDestinationIntoItself } from "./clipboard-helpers";
import type { Folder, ItemFile } from "../../type";

describe("isItemDestinationIntoItself", () => {
    it("Is into itself when destination and item are the same", () => {
        expect(isItemDestinationIntoItself([], 3, 3)).toBe(true);
    });

    it("Is into itself when the item is in the destination somewhere in hierarchy", () => {
        const folder_content = [
            {
                id: 1,
                parent_id: 0,
            } as Folder,
            {
                id: 2,
                parent_id: 1,
            } as ItemFile,
            {
                id: 3,
                parent_id: 2,
            } as ItemFile,
            {
                id: 4,
                parent_id: 3,
            } as ItemFile,
        ];
        expect(isItemDestinationIntoItself(folder_content, 2, 4)).toBe(true);
    });

    it("Is not considered into itself when the parent cannot be found", () => {
        const folder_content = [
            {
                id: 3,
                parent_id: 1,
            } as ItemFile,
        ];
        expect(isItemDestinationIntoItself(folder_content, 2, 3)).toBe(false);
    });

    it("Is not considered into itself when the root is reached", () => {
        const folder_content = [
            {
                id: 1,
                parent_id: 0,
            } as Folder,
            {
                id: 2,
                parent_id: 1,
            } as ItemFile,
        ];
        expect(isItemDestinationIntoItself(folder_content, 3, 2)).toBe(false);
    });
});
