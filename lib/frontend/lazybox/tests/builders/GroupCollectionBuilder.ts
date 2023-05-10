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

import type { GroupCollection, GroupOfItems } from "../../src/GroupCollection";
import { LazyboxItemStub } from "../stubs/LazyboxItemStub";
import { GroupOfItemsStub } from "../stubs/GroupOfItemsStub";

export const GroupCollectionBuilder = {
    withSingleGroup: (group: Partial<GroupOfItems>): GroupCollection => [
        GroupOfItemsStub.withDefaults(group),
    ],

    withTwoGroups: (): GroupCollection => [
        GroupOfItemsStub.withDefaults({
            label: "Group 1",
            items: [
                LazyboxItemStub.withDefaults({ value: { id: 0 } }),
                LazyboxItemStub.withDefaults({ value: { id: 1 } }),
                LazyboxItemStub.withDefaults({ value: { id: 2 } }),
            ],
        }),
        GroupOfItemsStub.withDefaults({
            label: "Group 2",
            items: [
                LazyboxItemStub.withDefaults({ value: { id: 3 } }),
                LazyboxItemStub.withDefaults({ value: { id: 4 } }),
                LazyboxItemStub.withDefaults({ value: { id: 5 }, is_disabled: true }),
            ],
        }),
    ],
};
