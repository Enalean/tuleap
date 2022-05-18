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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { ManageDropdown } from "../../src/dropdown/DropdownManager";

export type ManageDropdownStub = ManageDropdown & {
    getCloseLinkSelectorCallCount: () => number;
    getOpenLinkSelectorCallCount: () => number;
};

export const ManageDropdownStub = {
    withOpenDropdown: (): ManageDropdownStub => buildWithDropdownState({ is_open: true }),
    withClosedDropdown: (): ManageDropdownStub => buildWithDropdownState({ is_open: false }),
};

function buildWithDropdownState(state: { is_open: boolean }): ManageDropdownStub {
    let close_call_count = 0,
        open_call_count = 0,
        is_dropdown_open = state.is_open;

    return {
        isDropdownOpen(): boolean {
            return is_dropdown_open;
        },
        closeLinkSelector(): void {
            is_dropdown_open = false;
            close_call_count++;
        },
        openLinkSelector(): void {
            is_dropdown_open = true;
            open_call_count++;
        },
        getCloseLinkSelectorCallCount(): number {
            return close_call_count;
        },
        getOpenLinkSelectorCallCount(): number {
            return open_call_count;
        },
    };
}
