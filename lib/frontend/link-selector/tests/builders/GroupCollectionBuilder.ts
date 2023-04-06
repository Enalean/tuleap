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

import type { GroupCollection, GroupOfItems } from "../../src";

export const GroupCollectionBuilder = {
    withEmptyGroup: (): GroupCollection => [
        {
            label: "",
            empty_message: "irrelevant",
            items: [],
            is_loading: false,
            footer_message: "",
        },
    ],

    withSingleGroup: (group: Partial<GroupOfItems>): GroupCollection => [
        {
            label: "",
            empty_message: "",
            items: [],
            is_loading: false,
            footer_message: "",
            ...group,
        },
    ],

    withTwoGroups: (): GroupCollection => [
        {
            label: "Group 1",
            empty_message: "irrelevant",
            items: [
                { id: "value-0", value: { id: 0 }, is_disabled: false },
                { id: "value-1", value: { id: 1 }, is_disabled: false },
                { id: "value-2", value: { id: 2 }, is_disabled: false },
            ],
            is_loading: false,
            footer_message: "",
        },
        {
            label: "Group 2",
            empty_message: "irrelevant",
            items: [
                { id: "value-3", value: { id: 3 }, is_disabled: false },
                { id: "value-4", value: { id: 4 }, is_disabled: false },
                { id: "value-5", value: { id: 5 }, is_disabled: true },
            ],
            is_loading: false,
            footer_message: "",
        },
    ],
};
