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

import { User } from "../type";
import { haveAssigneesChanged } from "./have-assignees-changed";

describe("haveAssigneesChanged", () => {
    it("Returns true if a user is removed", () => {
        const assignees = [{ id: 101 } as User, { id: 102 } as User];
        const new_assignees_ids = [101];
        expect(haveAssigneesChanged(assignees, new_assignees_ids)).toBe(true);
    });

    it("Returns true if a user is added", () => {
        const assignees = [{ id: 101 } as User, { id: 102 } as User];
        const new_assignees_ids = [101, 102, 103];
        expect(haveAssigneesChanged(assignees, new_assignees_ids)).toBe(true);
    });

    it("Returns true if a user is added and another one is removed", () => {
        const assignees = [{ id: 101 } as User, { id: 102 } as User];
        const new_assignees_ids = [101, 103];
        expect(haveAssigneesChanged(assignees, new_assignees_ids)).toBe(true);
    });

    it("Returns false if arrays are not in the same order", () => {
        const assignees = [{ id: 101 } as User, { id: 102 } as User];
        const new_assignees_ids = [102, 101];
        expect(haveAssigneesChanged(assignees, new_assignees_ids)).toBe(false);
    });
});
