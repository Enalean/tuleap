/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { GroupCollection } from "../../src";

export const GroupCollectionBuilder = {
    withEmptyGroup: (): GroupCollection => [{ label: "", empty_message: "irrelevant", items: [] }],

    withSingleGroup: (): GroupCollection => [
        {
            label: "",
            empty_message: "irrelevant",
            items: [
                { value: { id: 0 } },
                { value: { id: 1 } },
                { value: { id: 2 } },
                { value: { id: 3 } },
            ],
        },
    ],

    withTwoGroups: (): GroupCollection => [
        {
            label: "Group 1",
            empty_message: "irrelevant",
            items: [{ value: { id: 0 } }, { value: { id: 1 } }, { value: { id: 2 } }],
        },
        {
            label: "Group 2",
            empty_message: "irrelevant",
            items: [{ value: { id: 3 } }, { value: { id: 4 } }, { value: { id: 5 } }],
        },
    ],
};
