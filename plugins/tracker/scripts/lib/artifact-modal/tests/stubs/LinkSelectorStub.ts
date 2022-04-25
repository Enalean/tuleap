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

import type { LinkSelector, GroupCollection } from "@tuleap/link-selector";

export interface LinkSelectorStub extends LinkSelector {
    getGroupCollection(): GroupCollection | undefined;
}

export const LinkSelectorStub = {
    withDropdownContentRecord: (): LinkSelectorStub => {
        let recorded_argument: GroupCollection | undefined;
        return {
            setDropdownContent(groups): void {
                recorded_argument = groups;
            },

            getGroupCollection(): GroupCollection | undefined {
                return recorded_argument;
            },

            resetSelection(): void {
                // Do nothing
            },

            destroy(): void {
                // Do nothing
            },
        };
    },
};
